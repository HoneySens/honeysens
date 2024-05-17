<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\Firmware;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Platform;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\ServiceManager;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Respect\Validation\Validator as V;

class PlatformsService {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_UNKNOWN_PLATFORM = 1;
    const CREATE_ERROR_DUPLICATE = 2;

    private ConfigParser $config;
    private EntityManager $em;
    private LogService $logger;
    private ServiceManager $serviceManager;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger, ServiceManager $serviceManager) {
        $this->config = $config;
        $this->em= $em;
        $this->logger = $logger;
        $this->serviceManager = $serviceManager;
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
        $qb = $this->em->createQueryBuilder();
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
        $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        return $firmware;
    }

    /**
     * Creates and persists a new firmware revision.
     * It expects binary file data as parameter and supports chunked uploads.
     *
     * @param array $data
     * @param User $user
     * @return Firmware
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createFirmware(array $data, User $user, TasksService $tasksService) {
        // Validation, we just expect a task id here
        V::arrayType()
            ->key('task', V::intVal())
            ->check($data);
        // Validate the given task
        $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($data['task']);
        V::objectType()->check($task);
        if($task->getUser() !== $user) throw new ForbiddenException();
        if($task->getType() != Task::TYPE_UPLOAD_VERIFIER || $task->getStatus() != Task::STATUS_DONE) throw new BadRequestException();
        $taskResult = $task->getResult();
        V::arrayType()->check($taskResult);
        V::key('valid', V::boolType())->check($taskResult);
        if(!$taskResult['valid']) throw new BadRequestException();
        if($taskResult['type'] != TasksService::UPLOAD_TYPE_PLATFORM_ARCHIVE) throw new BadRequestException();
        // Check platform existence
        $platform = $this->em->getRepository('HoneySens\app\models\entities\Platform')
            ->findOneBy(array('name' => $taskResult['platform']));
        if(!V::objectType()->validate($platform))
            throw new BadRequestException(self::CREATE_ERROR_UNKNOWN_PLATFORM);
        // Duplicate test
        $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')
            ->findOneBy(array('name' => $taskResult['name'], 'version' => $taskResult['version']));
        if(V::objectType()->validate($firmware))
            throw new BadRequestException(self::CREATE_ERROR_DUPLICATE);
        // Persistence
        $firmware = new Firmware();
        $firmware->setName($taskResult['name'])
            ->setVersion($taskResult['version'])
            ->setPlatform($platform)
            ->setDescription($taskResult['description'])
            ->setChangelog('')
            ->setPlatform($platform);
        $platform->addFirmwareRevision($firmware);
        $this->em->persist($firmware);
        // Set this firmware as default if there isn't a default yet
        if(!$platform->hasDefaultFirmwareRevision()) {
            $platform->setDefaultFirmwareRevision($firmware);
        }
        $this->em->flush();
        $platform->registerFirmware($firmware, sprintf('%s/%s/%s', DATA_PATH, TasksService::UPLOAD_PATH, $task->getParams()['path']), $this->config);
        // Remove upload verification task
        $tasksService->delete($task->getId(), $user);
        $this->logger->log(sprintf('Firmware revision %s for platform %s added', $firmware->getVersion(), $platform->getName()), LogEntry::RESOURCE_PLATFORMS, $platform->getId());
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
    public function updateFirmware($id, $data) {
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('default_firmware_revision', V::intVal())
            ->check($data);
        // Persistence
        $platform = $this->em->getRepository('HoneySens\app\models\entities\Platform')->find($id);
        V::objectType()->check($platform);
        $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($data['default_firmware_revision']);
        V::objectType()->check($firmware);
        $platform->setDefaultFirmwareRevision($firmware);
        $this->em->flush();
        $this->logger->log(sprintf('Default firmware revision for platform %s set to %s', $platform->getName(), $firmware->getVersion()), LogEntry::RESOURCE_PLATFORMS, $platform->getId());
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
    public function deleteFirmware($id) {
        // Validation
        V::intVal()->check($id);
        // Persistence
        $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        $platform = $firmware->getPlatform();
        V::objectType()->check($platform);
        // Don't remove the default firmware revision for this platform
        if($platform->getDefaultFirmwareRevision() == $firmware) throw new BadRequestException();
        // In case this revision is set as target firmware on some sensors, reset those back to their default revision
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Sensor', 's')
            ->where('s.firmware = :firmware')
            ->setParameter('firmware', $firmware);
        foreach($qb->getQuery()->getResult() as $sensor) {
            $sensor->setFirmware(null);
        }
        $platform->unregisterFirmware($firmware, $this->config);
        $this->em->remove($firmware);
        $this->em->flush();
        $this->logger->log(sprintf('Firmware revision %s of platform %s deleted', $firmware->getVersion(), $platform->getName()), LogEntry::RESOURCE_PLATFORMS, $platform->getId());
    }

    /**
     * Attempts to download the given firmware binary blob.
     * What exactly is offered to the client depends on the specific platform implementation.
     *
     * @param $id
     * @return array
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function getFirmwareDownload($id) {
        $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        $platform = $firmware->getPlatform();
        $firmwarePath = $platform->obtainFirmware($firmware, $this->config);
        $downloadName = sprintf('%s-%s.tar.gz', preg_replace('/\s+/', '-', strtolower($firmware->getName())), preg_replace('/\s+/', '-', strtolower($firmware->getVersion())));
        if($firmwarePath != null) return [$firmwarePath, $downloadName];
        else throw new BadRequestException();
    }

    /**
     * Attempts to identify and download the current default firmware for a given platform.
     *
     * @param $id
     * @return array
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function downloadCurrentFirmwareForPlatform($id) {
        $platform = $this->em->getRepository('HoneySens\app\models\entities\Platform')->find($id);
        V::objectType()->check($platform);
        $revision = $platform->getDefaultFirmwareRevision();
        V::objectType()->check($revision);
        return $this->getFirmwareDownload($revision->getId());
    }
}