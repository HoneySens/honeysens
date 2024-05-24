<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\adapters\RegistryAdapter;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Service;
use HoneySens\app\models\entities\ServiceRevision;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use Respect\Validation\Validator as V;

class SensorServicesService {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_REGISTRY_OFFLINE = 1;
    const CREATE_ERROR_DUPLICATE = 2;

    private EntityManager $em;
    private LogService $logger;
    private RegistryAdapter $registryAdapter;
    private TaskAdapter $taskAdapter;

    public function __construct(EntityManager $em, LogService $logger, RegistryAdapter $registryAdapter, TaskAdapter $taskAdapter) {
        $this->em= $em;
        $this->logger = $logger;
        $this->registryAdapter = $registryAdapter;
        $this->taskAdapter = $taskAdapter;
    }


    /**
     * Fetches services from the DB by various criteria:
     * - id: return the service with the given id
     * If no criteria are given, all services are returned.
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Service', 's');
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('s.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $services = array();
            foreach($qb->getQuery()->getResult() as $service) {
                $services[] = $service->getState();
            }
            return $services;
        }
    }

    /**
     * Creates and persists a new service (or revision).
     * A successfully completed upload verification task (id) is expected as input.
     *
     * @param array $data
     * @return Task
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create($data, User $sessionUser) {
        // Validation, we just expect a task id here
        V::arrayType()
            ->key('task', V::intVal())
            ->check($data);
        // Validate the given task
        $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($data['task']);
        V::objectType()->check($task);
        if($task->getUser() !== $sessionUser) throw new ForbiddenException();
        if($task->getType() != Task::TYPE_UPLOAD_VERIFIER || $task->getStatus() != Task::STATUS_DONE) throw new BadRequestException();
        $taskResult = $task->getResult();
        V::arrayType()->check($taskResult);
        V::key('valid', V::boolType())->check($taskResult);
        if(!$taskResult['valid']) throw new BadRequestException();
        if($taskResult['type'] != TasksService::UPLOAD_TYPE_SERVICE_ARCHIVE) throw new BadRequestException();
        // Check registry availability
        if(!$this->registryAdapter->isAvailable()) throw new BadRequestException(self::CREATE_ERROR_REGISTRY_OFFLINE);
        // Check for duplicates
        $service = $this->em->getRepository('HoneySens\app\models\entities\Service')
            ->findOneBy(array(
                'name' => $taskResult['name'],
                'repository' => $taskResult['repository']));
        $serviceRevision = $this->em->getRepository('HoneySens\app\models\entities\ServiceRevision')
            ->findOneBy(array(
                'service' => $service,
                'architecture' => $taskResult['architecture'],
                'revision' => $taskResult['revision']));
        if(V::objectType()->validate($service) && V::objectType()->validate($serviceRevision)) {
            // Error: The revision for this service is already registered
            throw new BadRequestException(self::CREATE_ERROR_DUPLICATE);
        }
        // Persist revision
        $serviceRevision = new ServiceRevision();
        $serviceRevision->setRevision($taskResult['revision'])
            ->setArchitecture($taskResult['architecture'])
            ->setRawNetworkAccess($taskResult['rawNetworkAccess'])
            ->setCatchAll($taskResult['catchAll'])
            ->setPortAssignment($taskResult['portAssignment'])
            ->setDescription($taskResult['revisionDescription']);
        $this->em->persist($serviceRevision);
        // Persist service if necessary
        if(!V::objectType()->validate($service)) {
            $service = new Service();
            $service->setName($taskResult['name'])
                ->setDescription($taskResult['description'])
                ->setRepository($taskResult['repository'])
                ->setDefaultRevision($serviceRevision->getRevision());
            $this->em->persist($service);
        }
        $service->addRevision($serviceRevision);
        // Remove upload verification task, but keep the uploaded file for the next task
        $taskResult['path'] = $task->getParams()['path'];
        $this->em->remove($task);
        $this->em->flush();
        // Enqueue registry upload task
        $task = $this->taskAdapter->enqueue($sessionUser, Task::TYPE_REGISTRY_MANAGER, $taskResult);
        $this->logger->log(sprintf('Service %s:%s (%s) added', $service->getName(), $serviceRevision->getRevision(),
            $serviceRevision->getArchitecture()), LogEntry::RESOURCE_SERVICES, $service->getId());
        return $task;
    }

    /**
     * Updates an existing service.
     *
     * The following parameters are recognized:
     * - default_revision: A division this service defaults to
     *
     * @param int $id
     * @param array $data
     * @return Service
     */
    public function update($id, $data) {
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('default_revision', V::stringType())
            ->check($data);
        // Persistence
        $service = $this->em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        V::objectType()->check($service);
        $defaultRevision = $this->em->getRepository('HoneySens\app\models\entities\ServiceRevision')
            ->findOneBy(array('revision' => $data['default_revision']));
        V::objectType()->check($defaultRevision);
        $service->setDefaultRevision($data['default_revision']);
        $this->em->flush();
        $this->logger->log(sprintf('Default revision for service %s set to %s', $service->getName(), $defaultRevision->getRevision()), LogEntry::RESOURCE_SERVICES, $service->getId());
        return $service;
    }

    public function delete($id) {
        // Validation
        V::intVal()->check($id);
        $service = $this->em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        V::objectType()->check($service);
        // Remove revisions and service from the registry
        foreach($service->getRevisions() as $revision) $this->removeServiceRevision($revision);
        $this->registryAdapter->removeRepository($service->getRepository());
        // Remove service from the db
        $sid = $service->getId();
        $this->em->remove($service);
        $this->em->flush();
        $this->logger->log(sprintf('Service %s and all associated revisions deleted', $service->getName()), LogEntry::RESOURCE_SERVICES, $sid);
    }

    /**
     * Deletes a single service revision identified by the given id
     *
     * @param int $id
     */
    public function deleteRevision($id) {
        // Validation
        V::intVal()->check($id);
        $serviceRevision = $this->em->getRepository('HoneySens\app\models\entities\ServiceRevision')->find($id);
        V::objectType()->check($serviceRevision);
        // Don't remove the default revision
        if($serviceRevision->getRevision() === $serviceRevision->getService()->getDefaultRevision()) throw new BadRequestException();
        $this->removeServiceRevision($serviceRevision);
        $this->em->flush();
        $service = $serviceRevision->getService();
        $this->logger->log(sprintf('Revision %s (%s) of service %s deleted', $serviceRevision->getRevision(), $serviceRevision->getArchitecture(), $service->getName()), LogEntry::RESOURCE_SERVICES, $service->getId());
    }

    /**
     * Queries the registry for availability and checks all registered services
     * for their status. Only returns true if all revisions of all registered
     * services have a matching template in the registry.
     *
     * @return array
     */
    public function getStatusSummary() {
        $registryStatus = $this->registryAdapter->isAvailable();
        $serviceStatus = true;
        if($registryStatus) {
            foreach($this->em->getRepository('HoneySens\app\models\entities\Service')->findAll() as $service) {
                try {
                    $ss = $this->getStatus($service->getId());
                    $serviceStatus = $serviceStatus && !in_array(false, $ss, true);
                } catch(\Exception $e) {
                    $serviceStatus = false;
                }
            }
        } else $serviceStatus = false;  // If the registry isn't available, services aren't either
        return array('registry' => $registryStatus, 'services' => $serviceStatus);
    }

    /**
     * Used to query individual service status from the registry. This basically lists for each service revision
     * registered in the db whether there is a matching template registered in the docker service registry.
     *
     * @param int $id
     * @throws NotFoundException
     * @return array;
     */
    public function getStatus($id) {
        V::intVal()->check($id);
        $service = $this->em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        if(!V::objectType()->validate($service)) throw new NotFoundException();
        $tags = $this->registryAdapter->getTags($service->getRepository());
        V::arrayType()->check($tags);
        $result = array();
        foreach($service->getRevisions() as $revision) {
            $result[$revision->getId()] = in_array(sprintf('%s-%s', $revision->getArchitecture(), $revision->getRevision()), $tags);
        }
        return $result;
    }

    /**
     * Removes a service revision from the registry and marks it for removal in the DB
     *
     * @param ServiceRevision $serviceRevision
     */
    private function removeServiceRevision(ServiceRevision $serviceRevision) {
        $repository = $serviceRevision->getService()->getRepository();
        try {
            $this->registryAdapter
                ->removeTag($repository, sprintf('%s-%s', $serviceRevision->getArchitecture(), $serviceRevision->getRevision()));
        } catch (NotFoundException $e) {
            // The registry is online, but doesn't contain this image -> we can continue
        }
        $this->em->remove($serviceRevision);
    }
}
