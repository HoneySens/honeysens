<?php
namespace HoneySens\app\controllers;
use FileUpload\PathResolver;
use FileUpload\FileSystem;
use FileUpload\FileUpload;
use FileUpload\File;

use HoneySens\app\models\entities\Service;
use HoneySens\app\models\entities\ServiceRevision;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

class Services extends RESTResource {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_INVALID_IMAGE = 1;
    const CREATE_ERROR_INVALID_METADATA = 2;
    const CREATE_ERROR_DUPLICATE = 3;

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/services(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            $criteria = array();
            $criteria['id'] = $id;
            $result = $controller->get($criteria);
            echo json_encode($result);
        });

        $app->get('/api/services/registry', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            if($controller->getRegistryStatus()) echo json_encode([]);
            else throw new NotFoundException();
        });

        $app->get('/api/services/:id/status', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            $result = $controller->getStatus($id);
            echo json_encode($result);
        });

        $app->post('/api/services', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            $serviceData = $controller->create($_FILES['service']);
            echo json_encode($serviceData);
        });

        $app->put('/api/services/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $serviceData = json_decode($request);
            $service = $controller->update($id, $serviceData);
            echo json_encode($service->getState());
        });

        $app->delete('/api/services/revisions/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            $controller->deleteRevision($id);
            echo json_encode([]);
        });

        $app->delete('/api/services/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Services($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
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
     * Queries the registry for availability
     *
     * @return bool
     */
    public function getRegistryStatus() {
        $this->assureAllowed('get');
        return $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY)->isAvailable();
    }

    /**
     * Used to query the individual service status from the registry. This basically lists for each service revision
     * registered in the db whether there is a matching template registered in the docker service registry.
     *
     * @param int $id
     * @throws NotFoundException
     * @return array;
     */
    public function getStatus($id) {
        $this->assureAllowed('get');
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
     * Binary file data is expected as parameter, chunked uploads are supported.
     *
     * @param string $data
     * @return array
     */
    public function create($data) {
        $this->assureAllowed('create');
        $em = $this->getEntityManager();
        $pathresolver = new PathResolver\Simple(realpath(APPLICATION_PATH . '/../data/upload'));
        $fs = new FileSystem\Simple();
        $fileUpload = new FileUpload($data, $_SERVER);
        $fileUpload->setPathResolver($pathresolver);
        $fileUpload->setFileSystem($fs);
        $fileUpload->addCallback('completed', function(File $file) {
            global $em;
            // Check registry availability
            $registryService = $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY);
            // Check archive content
            exec('/bin/tar tzf ' . escapeshellarg($file->getRealPath()), $output);
            if(!$registryService->isAvailable() && !in_array('service.tar', $output) || !in_array('metadata.xml', $output)) {
                $this->removeFile($file);
                throw new BadRequestException(Services::CREATE_ERROR_INVALID_IMAGE);
            }
            // Check metadata
            $output = array();
            exec('/bin/tar xzf ' . escapeshellarg($file->getRealPath()) . ' metadata.xml -O', $output);
            try {
                $metadata = new \SimpleXMLElement(implode($output));
            } catch(\Exception $e) {
                $this->removeFile($file);
                throw new BadRequestException(Services::CREATE_ERROR_INVALID_METADATA);
            }
            V::objectType()
                ->attribute('name')
                ->attribute('architecture')
                ->attribute('rawNetworkAccess')
                ->attribute('catchAll')
                ->attribute('portAssignment')
                ->attribute('repository')
                ->attribute('description')
                ->attribute('revision')
                ->attribute('revisionDescription')
                ->check($metadata);
            // Check for duplicates
            $service = $em->getRepository('HoneySens\app\models\entities\Service')
                ->findOneBy(array(
                    'name' => (string) $metadata->name,
                    'repository' => (string) $metadata->repository));
            $serviceRevision = $em->getRepository('HoneySens\app\models\entities\ServiceRevision')
                ->findOneBy(array(
                    'service' => $service,
                    'architecture' => (string) $metadata->architecture,
                    'revision' => (string) $metadata->revision));
            if(V::objectType()->validate($service) && V::objectType()->validate($serviceRevision)) {
                $this->removeFile($file);
                throw new BadRequestException(Services::CREATE_ERROR_DUPLICATE);
            }
            // Persist revision
            $this->getServiceManager()->get(ServiceManager::SERVICE_BEANSTALK)
                ->putServiceRegistryJob(sprintf('%s:%s-%s',
                    (string) $metadata->repository,
                    (string) $metadata->architecture,
                    (string) $metadata->revision),
                    $file->getRealPath(), 'service.tar');
            $serviceRevision = new ServiceRevision();
            $serviceRevision->setRevision((string) $metadata->revision)
                ->setArchitecture((string) $metadata->architecture)
                ->setRawNetworkAccess(((string)$metadata->rawNetworkAccess) === 'true')
                ->setCatchAll(((string)$metadata->catchAll) === 'true')
                ->setPortAssignment((string) $metadata->portAssignment)
                ->setDescription((string) $metadata->revisionDescription);
            $em->persist($serviceRevision);
            // Persist service if necessary
            if(!V::objectType()->validate($service)) {
                $service = new Service();
                $service->setName((string) $metadata->name)
                    ->setDescription((string) $metadata->description)
                    ->setRepository((string) $metadata->repository)
                    ->setDefaultRevision($serviceRevision->getRevision());
                $em->persist($service);
            }
            $service->addRevision($serviceRevision);
            $em->flush();
            $file->service = $serviceRevision->getState();
        });
        list($files, $headers) = $fileUpload->processAll();
        foreach($headers as $header => $value) {
            header($header . ': ' . $value);
        }
        // The array with the 'files' key is required by the fileupload plugin used for the frontend
        return array('files' => $files);
    }

    /**
     * Updates an existing service.
     *
     * The following parameters are recognized:
     * - default_revision: A division this service defaults to
     *
     * @param int $id
     * @param \stdClass $data
     * @return Service
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('default_revision', V::stringType())
            ->check($data);
        // Persistence
        $em = $this->getEntityManager();
        $service = $em->getRepository('HoneySens\app\models\entities\Service')->find($id);
        V::objectType()->check($service);
        $defaultRevision = $em->getRepository('HoneySens\app\models\entities\ServiceRevision')
            ->findOneBy(array('revision' => $data->default_revision));
        V::objectType()->check($defaultRevision);
        $service->setDefaultRevision($data->default_revision);
        $em->flush();
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
        // TODO Don't remove revisions that are either default or otherwise in use by sensors
        foreach($service->getRevisions() as $revision) $this->removeServiceRevision($revision);
        $this->getServiceManager()->get(ServiceManager::SERVICE_REGISTRY)->removeRepository($service->getRepository());
        // Remove service from the db
        $em->remove($service);
        $em->flush();
    }

    /**
     * Deletes a single service revision identified by the given id
     *
     * @param int $id
     */
    public function deleteRevision($id) {
        // TODO Don't remove revisions that are either default or otherwise in use by sensors
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $serviceRevision = $em->getRepository('HoneySens\app\models\entities\ServiceRevision')->find($id);
        V::objectType()->check($serviceRevision);
        $this->removeServiceRevision($serviceRevision);
        $em->flush();
    }

    /**
     * Attempts to remove an uploaded file if it exists
     *
     * @param \FileUpload\File $file
     */
    private function removeFile(File $file) {
        if(file_exists($file->getRealPath())) exec('rm ' . escapeshellarg($file->getRealPath()));
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