<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Service;
use HoneySens\app\models\entities\ServiceRevision;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Services extends RESTResource {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_REGISTRY_OFFLINE = 1;
    const CREATE_ERROR_DUPLICATE = 2;

    static function registerRoutes($app, $em, $services, $config) {
        $app->get('/api/services[/{id:\d+}]', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $criteria = array('id' => $args['id'] ?? null);
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        $app->get('/api/services/status', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $response->getBody()->write(json_encode($controller->getStatusSummary()));
            return $response;
        });

        $app->get('/api/services/{id:\d+}/status', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $result = $controller->getStatus($args['id']);
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        // Requires a reference to a successfully completed verification task.
        $app->post('/api/services', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $service = $controller->create($request->getParsedBody());
            $response->getBody()->write(json_encode($service->getState()));
            return $response;
        });

        $app->put('/api/services/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $service = $controller->update($args['id'], $request->getParsedBody());
            $response->getBody()->write(json_encode($service->getState()));
            return $response;
        });

        $app->delete('/api/services/revisions/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $controller->deleteRevision($args['id']);
            $response->getBody()->write(json_encode([]));
            return $response;
        });

        $app->delete('/api/services/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Services($em, $services, $config);
            $controller->delete($args['id']);
            $response->getBody()->write(json_encode([]));
            return $response;
        });
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
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
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
     * Queries the registry for availability and checks all registered services
     * for their status. Only returns true if all revisionsof all registered
     * services have a matching template in the registry.
     *
     * @return array
     */
    public function getStatusSummary() {
        $this->assureAllowed('get');
        $registryStatus = $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY)->isAvailable();
        $serviceStatus = true;
        if($registryStatus) {
            foreach($this->getEntityManager()->getRepository('HoneySens\app\models\entities\Service')->findAll() as $service) {
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
        $this->assureAllowed('get');
        V::intVal()->check($id);
        $service = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Service')->find($id);
        if(!V::objectType()->validate($service)) throw new NotFoundException();
        $tags = $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY)->getTags($service->getRepository());
        V::arrayType()->check($tags);
        $result = array();
        foreach($service->getRevisions() as $revision) {
            $result[$revision->getId()] = in_array(sprintf('%s-%s', $revision->getArchitecture(), $revision->getRevision()), $tags);
        }
        return $result;
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
    public function create($data) {
        $this->assureAllowed('create');
        $em = $this->getEntityManager();
        // Validation, we just expect a task id here
        V::arrayType()
            ->key('task', V::intVal())
            ->check($data);
        // Validate the given task
        $task = $em->getRepository('HoneySens\app\models\entities\Task')->find($data['task']);
        V::objectType()->check($task);
        if($task->getUser() !== $this->getSessionUser()) throw new ForbiddenException();
        if($task->getType() != Task::TYPE_UPLOAD_VERIFIER || $task->getStatus() != Task::STATUS_DONE) throw new BadRequestException();
        $taskResult = $task->getResult();
        V::arrayType()->check($taskResult);
        V::key('valid', V::boolType())->check($taskResult);
        if(!$taskResult['valid']) throw new BadRequestException();
        if($taskResult['type'] != Tasks::UPLOAD_TYPE_SERVICE_ARCHIVE) throw new BadRequestException();
        // Check registry availability
        $registryService = $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY);
        if(!$registryService->isAvailable()) throw new BadRequestException(self::CREATE_ERROR_REGISTRY_OFFLINE);
        // Check for duplicates
        $service = $em->getRepository('HoneySens\app\models\entities\Service')
            ->findOneBy(array(
                'name' => $taskResult['name'],
                'repository' => $taskResult['repository']));
        $serviceRevision = $em->getRepository('HoneySens\app\models\entities\ServiceRevision')
            ->findOneBy(array(
                'service' => $service,
                'architecture' => $taskResult['architecture'],
                'revision' => $taskResult['revision']));
        if(V::objectType()->validate($service) && V::objectType()->validate($serviceRevision)) {
            // Error: The revision for this service is already registered
            throw new BadRequestException(Services::CREATE_ERROR_DUPLICATE);
        }
        // Persist revision
        $serviceRevision = new ServiceRevision();
        $serviceRevision->setRevision($taskResult['revision'])
            ->setArchitecture($taskResult['architecture'])
            ->setRawNetworkAccess($taskResult['rawNetworkAccess'])
            ->setCatchAll($taskResult['catchAll'])
            ->setPortAssignment($taskResult['portAssignment'])
            ->setDescription($taskResult['revisionDescription']);
        $em->persist($serviceRevision);
        // Persist service if necessary
        if(!V::objectType()->validate($service)) {
            $service = new Service();
            $service->setName($taskResult['name'])
                ->setDescription($taskResult['description'])
                ->setRepository($taskResult['repository'])
                ->setDefaultRevision($serviceRevision->getRevision());
            $em->persist($service);
        }
        $service->addRevision($serviceRevision);
        // Remove upload verification task, but keep the uploaded file for the next task
        $taskResult['path'] = $task->getParams()['path'];
        $em->remove($task);
        $em->flush();
        // Enqueue registry upload task
        $task = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->enqueue($this->getSessionUser(), Task::TYPE_REGISTRY_MANAGER, $taskResult);
        $this->log(sprintf('Service %s:%s (%s) added', $service->getName(), $serviceRevision->getRevision(),
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
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('default_revision', V::stringType())
            ->check($data);
        // Persistence
        $em = $this->getEntityManager();
        $service = $em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        V::objectType()->check($service);
        $defaultRevision = $em->getRepository('HoneySens\app\models\entities\ServiceRevision')
            ->findOneBy(array('revision' => $data['default_revision']));
        V::objectType()->check($defaultRevision);
        $service->setDefaultRevision($data['default_revision']);
        $em->flush();
        $this->log(sprintf('Default revision for service %s set to %s', $service->getName(), $defaultRevision->getRevision()), LogEntry::RESOURCE_SERVICES, $service->getId());
        return $service;
    }

    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $service = $em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        V::objectType()->check($service);
        // Remove revisions and service from the registry
        foreach($service->getRevisions() as $revision) $this->removeServiceRevision($revision);
        $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY)->removeRepository($service->getRepository());
        // Remove service from the db
        $sid = $service->getId();
        $em->remove($service);
        $em->flush();
        $this->log(sprintf('Service %s and all associated revisions deleted', $service->getName()), LogEntry::RESOURCE_SERVICES, $sid);
    }

    /**
     * Deletes a single service revision identified by the given id
     *
     * @param int $id
     */
    public function deleteRevision($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $serviceRevision = $em->getRepository('HoneySens\app\models\entities\ServiceRevision')->find($id);
        V::objectType()->check($serviceRevision);
        // Don't remove the default revision
        if($serviceRevision->getRevision() === $serviceRevision->getService()->getDefaultRevision()) throw new BadRequestException();
        $this->removeServiceRevision($serviceRevision);
        $em->flush();
        $service = $serviceRevision->getService();
        $this->log(sprintf('Revision %s (%s) of service %s deleted', $serviceRevision->getRevision(), $serviceRevision->getArchitecture(), $service->getName()), LogEntry::RESOURCE_SERVICES, $service->getId());
    }

    /**
     * Removes a service revision from the registry and marks it for removal in the DB
     *
     * @param ServiceRevision $serviceRevision
     */
    private function removeServiceRevision(ServiceRevision $serviceRevision) {
        $repository = $serviceRevision->getService()->getRepository();
        try {
            $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY)
                ->removeTag($repository, sprintf('%s-%s', $serviceRevision->getArchitecture(), $serviceRevision->getRevision()));
        } catch (NotFoundException $e) {
            // The registry is online, but doesn't contain this image -> we can continue
        }
        $this->getEntityManager()->remove($serviceRevision);
    }
}
