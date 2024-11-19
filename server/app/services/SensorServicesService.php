<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use HoneySens\app\adapters\RegistryAdapter;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\TaskStatus;
use HoneySens\app\models\constants\TaskType;
use HoneySens\app\models\entities\ServiceRevision;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;

class SensorServicesService extends Service {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_REGISTRY_OFFLINE = 1;
    const CREATE_ERROR_DUPLICATE = 2;

    private LogService $logger;
    private RegistryAdapter $registryAdapter;
    private TaskAdapter $taskAdapter;

    public function __construct(EntityManager $em, LogService $logger, RegistryAdapter $registryAdapter, TaskAdapter $taskAdapter) {
        parent::__construct($em);
        $this->logger = $logger;
        $this->registryAdapter = $registryAdapter;
        $this->taskAdapter = $taskAdapter;
    }


    /**
     * Fetches sensor services from the DB.
     *
     * @param int|null $id ID of a specific service to fetch
     * @throws NotFoundException
     */
    public function getServices(?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Service', 's');
        try {
            if ($id !== null) {
                $qb->andWhere('s.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                $services = array();
                foreach ($qb->getQuery()->getResult() as $service) {
                    $services[] = $service->getState();
                }
                return $services;
            }
        } catch(NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Creates a new revision for a sensor service.
     * Requires a reference to a finished upload verification tasks owned by the given user that references a service
     * archive. Also automatically creates the referenced sensor service in case it's currently unknown.
     * Returns a task that uploads the service revision into the registry.
     *
     * @param User $user The user which performs this operation, has to own the given task
     * @param int $taskId ID of a completed upload verification task
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     * @todo Let task results return a proper object
     */
    public function createService(User $user, int $taskId): Task {
        try {
            $serviceRepository = $this->em->getRepository('HoneySens\app\models\entities\Service');
            $serviceRevisionRepository = $this->em->getRepository('HoneySens\app\models\entities\ServiceRevision');
            $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($taskId);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($task === null) throw new BadRequestException();
        if($task->user !== $user) throw new ForbiddenException();
        if($task->type !== TaskType::UPLOAD_VERIFIER || $task->status !== TaskStatus::DONE)
            throw new BadRequestException();
        $taskResult = $task->result;
        if($taskResult === null ||
            !array_key_exists('valid', $taskResult) ||
            !$taskResult['valid'] ||
            $taskResult['type'] !== TasksService::UPLOAD_TYPE_SERVICE_ARCHIVE)
            throw new BadRequestException();
        if(!$this->registryAdapter->isAvailable())
            throw new BadRequestException(self::CREATE_ERROR_REGISTRY_OFFLINE);
        // Check for duplicates
        $service = $serviceRepository->findOneBy(array(
                'name' => $taskResult['name'],
                'repository' => $taskResult['repository']));
        $serviceRevision = $serviceRevisionRepository->findOneBy(array(
                'service' => $service,
                'architecture' => $taskResult['architecture'],
                'revision' => $taskResult['revision']));
        if($service !== null && $serviceRevision !== null) {
            // Error: The revision for this service is already registered
            throw new BadRequestException(self::CREATE_ERROR_DUPLICATE);
        }
        // Persist revision
        $serviceRevision = new ServiceRevision();
        $serviceRevision->revision = $taskResult['revision'];
        $serviceRevision->architecture = $taskResult['architecture'];
        $serviceRevision->rawNetworkAccess = $taskResult['rawNetworkAccess'];
        $serviceRevision->catchAll = $taskResult['catchAll'];
        $serviceRevision->portAssignment = $taskResult['portAssignment'];
        $serviceRevision->description = $taskResult['revisionDescription'];
        try {
            $this->em->persist($serviceRevision);
            // Persist service if necessary
            if ($service === null) {
                $service = new \HoneySens\app\models\entities\Service();
                $service->name = $taskResult['name'];
                $service->description = $taskResult['description'];
                $service->repository = $taskResult['repository'];
                $service->defaultRevision = $serviceRevision->revision;
                $this->em->persist($service);
            }
            $service->addRevision($serviceRevision);
            // Remove upload verification task, but keep the uploaded file for the next task
            $taskResult['path'] = $task->params['path'];
            $this->em->remove($task);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        // Enqueue registry upload task
        $task = $this->taskAdapter->enqueue($user, TaskType::REGISTRY_MANAGER, $taskResult);
        $this->logger->log(sprintf('Service %s:%s (%s) added', $service->name, $serviceRevision->revision,
            $serviceRevision->architecture), LogResource::SERVICES, $service->getId());
        return $task;
    }

    /**
     * Sets the default revision of an existing sensor service.
     *
     * @param int $id ID of the service to update
     * @param string $defaultRevision The revision name/tag this service defaults to
     * @throws NotFoundException
     * @throws SystemException
     */
    public function updateService(int $id, string $defaultRevision): \HoneySens\app\models\entities\Service {
        try {
            $service = $this->em->getRepository('HoneySens\app\models\entities\Service')->find($id);
            if($service === null) throw new NotFoundException();
            $targetRevision = $this->em
                ->getRepository('HoneySens\app\models\entities\ServiceRevision')
                ->findOneBy(array('revision' => $defaultRevision));
            if($targetRevision === null) throw new NotFoundException();
            $service->defaultRevision = $defaultRevision;
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Default revision for service %s set to %s', $service->name, $defaultRevision), LogResource::SERVICES, $service->getId());
        return $service;
    }

    /**
     * Removes a specific service and all of its associated revisions.
     *
     * @param int $id Service ID to delete
     * @throws NotFoundException
     * @throws SystemException
     */
    public function deleteService(int $id): void {
        $service = $this->em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        if($service === null) throw new NotFoundException();
        // Remove revisions and service from the registry
        foreach($service->getRevisions() as $revision) $this->removeServiceRevision($revision);
        $this->registryAdapter->removeRepository($service->repository);
        try {
            // Remove service from the db
            $this->em->remove($service);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Service %s and all associated revisions deleted', $service->name), LogResource::SERVICES, $id);
    }

    /**
     * Removes a revision of a specific service.
     *
     * @param int $id Revision ID to delete
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function deleteRevision(int $id): void {
        $serviceRevision = $this->em->getRepository('HoneySens\app\models\entities\ServiceRevision')->find($id);
        if($serviceRevision === null) throw new NotFoundException();
        // Don't remove the default revision
        $service = $serviceRevision->service;
        if($serviceRevision->revision === $service->defaultRevision) throw new BadRequestException();
        try {
            $this->removeServiceRevision($serviceRevision);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Revision %s (%s) of service %s deleted', $serviceRevision->revision, $serviceRevision->architecture, $service->name), LogResource::SERVICES, $service->getId());
    }

    /**
     * Queries the registry for availability and checks all registered services
     * for their status. Returns an array ['registry' => bool, 'services' => bool]:
     * The value of 'registry' determines the availability of the registry itself,
     * 'services' is a consolidated status report for all services: If any registered
     * service is missing in the registry, this is false.
     *
     * @return array
     * @throws SystemException
     */
    public function getStatusSummary(): array {
        $registryStatus = $this->registryAdapter->isAvailable();
        $serviceStatus = true;
        try {
            if ($registryStatus) {
                foreach ($this->em->getRepository('HoneySens\app\models\entities\Service')->findAll() as $service) {
                    try {
                        $ss = $this->getServiceStatus($service->getId());
                        $serviceStatus = $serviceStatus && !in_array(false, $ss, true);
                    } catch (NotFoundException) {
                        $serviceStatus = false;
                    }
                }
            } else $serviceStatus = false;  // If the registry isn't available, services aren't either
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        return array('registry' => $registryStatus, 'services' => $serviceStatus);
    }

    /**
     * Used to query individual service status from the registry. This basically lists for each service revision
     * registered in the db whether there is a matching template registered in the docker service registry.
     *
     * @param int $id Service ID to query
     * @throws NotFoundException
     */
    public function getServiceStatus(int $id): array {
        $service = $this->em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        if($service === null) throw new NotFoundException();
        $tags = $this->registryAdapter->getTags($service->repository);
        $result = array();
        foreach($service->getRevisions() as $revision) {
            $result[$revision->getId()] = in_array(sprintf('%s-%s', $revision->architecture, $revision->revision), $tags);
        }
        return $result;
    }

    /**
     * Removes a service revision from the registry and marks it for removal in the DB.
     *
     * @param ServiceRevision $serviceRevision Instance of the service revision do delete.
     * @throws BadRequestException
     * @throws SystemException
     */
    private function removeServiceRevision(ServiceRevision $serviceRevision) {
        $repository = $serviceRevision->service->repository;
        try {
            $this->registryAdapter
                ->removeTag($repository, sprintf('%s-%s', $serviceRevision->architecture, $serviceRevision->revision));
        } catch (NotFoundException $e) {
            // The registry is online, but doesn't contain this image (anymore)
        }
        try {
            $this->em->remove($serviceRevision);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
    }
}
