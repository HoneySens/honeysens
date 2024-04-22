<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\Firmware;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Platform;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use \Slim\Routing\RouteCollectorProxy;

class Platforms extends RESTResource {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_UNKNOWN_PLATFORM = 1;
    const CREATE_ERROR_DUPLICATE = 2;

    static function registerRoutes($platforms, $em, $services, $config) {
        $platforms->get('[/{id:\d+}]', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $criteria = array('id' => $args['id'] ?? null);
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        $platforms->get('/{id:\d+}/firmware/current', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $controller->downloadCurrentFirmwareForPlatform($args['id']);
        });

        $platforms->get('/firmware/{id:\d+}/raw', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $controller->downloadFirmware($args['id']);
        });

        $platforms->get('/firmware/{id:\d+}', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $firmware = $controller->getFirmware($args['id']);
            $response->getBody()->write(json_encode($firmware->getState()));
            return $response;
        });

        // Requires a successfully completed verification task
        $platforms->post('/firmware', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $firmware = $controller->create($request->getParsedBody());
            $response->getBody()->write(json_encode($firmware->getState()));
            return $response;
        });

        $platforms->put('/{id:\d+}', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $platform = $controller->update($args['id'], $request->getParsedBody());
            $response->getBody()->write(json_encode($platform->getState()));
            return $response;
        });

        $platforms->delete('/firmware/{id:\d+}', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Platforms($em, $services, $config);
            $controller->delete($args['id']);
            $response->getBody()->write(json_encode([]));
            return $response;
        });
    }

    /**
     * Fetches platforms from the DB by various criteria:
     * - id: returns the platform with the given id
     * If no criteria are given, all platforms are returned.
     *
     * @param array $criteria
     * @return array
     * @throws ForbiddenException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')->from('HoneySens\app\models\entities\Platform', 'p');
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $platforms = array();
            foreach($qb->getQuery()->getResult() as $platform) {
                $platforms[] = $platform->getState();
            }
            return $platforms;
        }
    }

    /**
     * Fetches the firmware with the given id.
     *
     * @param $id
     * @return Firmware
     * @throws ForbiddenException
     */
    public function getFirmware($id) {
        $this->assureAllowed('get');
        $firmware = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        return $firmware;
    }

    /**
     * Attempts to download the given firmware binary blob.
     * What exactly is offered to the client depends on the specific platform implementation.
     *
     * @param $id
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function downloadFirmware($id) {
        // Authenticate either as sensor or with a user session
        $sensor = null;
        try {
            $sensor = $this->validateSensorRequest('get');
        } catch (ForbiddenException $e) {
            $this->assureAllowed('download');
        }
        $firmware = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        $platform = $firmware->getPlatform();
        $firmwarePath = $platform->obtainFirmware($firmware, $this->getConfig());
        // session_write_close(); Necessary?
        $downloadName = sprintf('%s-%s.tar.gz', preg_replace('/\s+/', '-', strtolower($firmware->getName())), preg_replace('/\s+/', '-', strtolower($firmware->getVersion())));
        if($sensor != null) $this->setMACHeaders($sensor, 'get');
        if($firmwarePath != null) $this->offerFile($firmwarePath, $downloadName);
        else throw new BadRequestException();
    }

    /**
     * Attempts to identify and download the current default firmware for a given platform.
     *
     * @param $id
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function downloadCurrentFirmwareForPlatform($id) {
        $this->assureAllowed('download');
        $platform = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Platform')->find($id);
        V::objectType()->check($platform);
        $revision = $platform->getDefaultFirmwareRevision();
        V::objectType()->check($revision);
        $this->downloadFirmware($revision->getId());
    }

    /**
     * Creates and persists a new firmware revision.
     * It expects binary file data as parameter and supports chunked uploads.
     *
     * @param array $data
     * @return Firmware
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
        if($taskResult['type'] != Tasks::UPLOAD_TYPE_PLATFORM_ARCHIVE) throw new BadRequestException();
        // Check platform existence
        $platform = $em->getRepository('HoneySens\app\models\entities\Platform')
            ->findOneBy(array('name' => $taskResult['platform']));
        if(!V::objectType()->validate($platform))
            throw new BadRequestException(Platforms::CREATE_ERROR_UNKNOWN_PLATFORM);
        // Duplicate test
        $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')
            ->findOneBy(array('name' => $taskResult['name'], 'version' => $taskResult['version']));
        if(V::objectType()->validate($firmware))
            throw new BadRequestException(Platforms::CREATE_ERROR_DUPLICATE);
        // Persistence
        $firmware = new Firmware();
        $firmware->setName($taskResult['name'])
            ->setVersion($taskResult['version'])
            ->setPlatform($platform)
            ->setDescription($taskResult['description'])
            ->setChangelog('')
            ->setPlatform($platform);
        $platform->addFirmwareRevision($firmware);
        $em->persist($firmware);
        // Set this firmware as default if there isn't a default yet
        if(!$platform->hasDefaultFirmwareRevision()) {
            $platform->setDefaultFirmwareRevision($firmware);
        }
        $em->flush();
        $platform->registerFirmware($firmware, sprintf('%s/%s/%s', DATA_PATH, Tasks::UPLOAD_PATH, $task->getParams()['path']), $this->getConfig());
        // Remove upload verification task
        $taskController = new Tasks($em, $this->getServiceManager(), $this->getConfig());
        $taskController->delete($task->getId());
        $this->log(sprintf('Firmware revision %s for platform %s added', $firmware->getVersion(), $platform->getName()), LogEntry::RESOURCE_PLATFORMS, $platform->getId());
        return $firmware;
    }

    /**
     * Updates platform metadata.
     * Only the default firmware revision can be changed.
     *
     * @param int $id
     * @param array $data
     * @return Platform
     * @throws ForbiddenException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('default_firmware_revision', V::intVal())
            ->check($data);
        // Persistence
        $em = $this->getEntityManager();
        $platform = $em->getRepository('HoneySens\app\models\entities\Platform')->find($id);
        V::objectType()->check($platform);
        $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')->find($data['default_firmware_revision']);
        V::objectType()->check($firmware);
        $platform->setDefaultFirmwareRevision($firmware);
        $em->flush();
        $this->log(sprintf('Default firmware revision for platform %s set to %s', $platform->getName(), $firmware->getVersion()), LogEntry::RESOURCE_PLATFORMS, $platform->getId());
        return $platform;
    }

    /**
     * Removes a firmware revision from the given platform.
     *
     * @param $id
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        // Persistence
        $em = $this->getEntityManager();
        $firmware = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        $platform = $firmware->getPlatform();
        V::objectType()->check($platform);
        // Don't remove the default firmware revision for this platform
        if($platform->getDefaultFirmwareRevision() == $firmware) throw new BadRequestException();
        // In case this revision is set as target firmware on some sensors, reset those back to their default revision
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Sensor', 's')
            ->where('s.firmware = :firmware')
            ->setParameter('firmware', $firmware);
        foreach($qb->getQuery()->getResult() as $sensor) {
            $sensor->setFirmware(null);
        }
        $platform->unregisterFirmware($firmware, $this->getConfig());
        $em->remove($firmware);
        $em->flush();
        $this->log(sprintf('Firmware revision %s of platform %s deleted', $firmware->getVersion(), $platform->getName()), LogEntry::RESOURCE_PLATFORMS, $platform->getId());
    }
}
