<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\TaskStatus;
use HoneySens\app\models\constants\TaskType;
use HoneySens\app\models\entities\Firmware;
use HoneySens\app\models\entities\Platform;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;

class PlatformsService extends Service {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_UNKNOWN_PLATFORM = 1;
    const CREATE_ERROR_DUPLICATE = 2;

    private LogService $logger;
    private TasksService $tasksService;

    public function __construct(EntityManager $em, LogService $logger, TasksService $tasksService) {
        parent::__construct($em);
        $this->logger = $logger;
        $this->tasksService = $tasksService;
    }

    /**
     * Fetches platforms from the DB.
     *
     * @param int|null $id OD of a specific platform to fetch
     * @throws NotFoundException
     */
    public function getPlatforms(?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')->from('HoneySens\app\models\entities\Platform', 'p');
        try {
            if ($id !== null) {
                $qb->andWhere('p.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                $platforms = array();
                foreach ($qb->getQuery()->getResult() as $platform) {
                    $platforms[] = $platform->getState();
                }
                return $platforms;
            }
        } catch(NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Fetches the firmware with the given ID.
     *
     * @param int $id Firmware identifier
     * @throws NotFoundException
     * @throws SystemException
     */
    public function getFirmware(int $id): Firmware {
        try {
            $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($firmware === null) throw new NotFoundException();
        return $firmware;
    }

    /**
     * Creates and persists a new firmware revision.
     * Requires a successfully completed upload verification task ID.
     *
     * @param User $user Session user that calls this service, needs to be in possession of $taskId
     * @param int $taskId ID of a successfully completed upload verification task
     * @return Firmware
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function createFirmware(User $user, int $taskId): Firmware {
        // Task validation
        try {
            $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($taskId);
            if($task === null) throw new NotFoundException();
            if($task->user !== $user) throw new ForbiddenException();
            if($task->type !== TaskType::UPLOAD_VERIFIER || $task->status !== TaskStatus::DONE)
                throw new BadRequestException();
            $taskResult = $task->result;
            if($taskResult === null) throw new BadRequestException();
            if(!$taskResult['valid'] || $taskResult['type'] != TasksService::UPLOAD_TYPE_PLATFORM_ARCHIVE)
                throw new BadRequestException();
            // Check platform existence
            $platform = $this->em->getRepository('HoneySens\app\models\entities\Platform')
                ->findOneBy(array('name' => $taskResult['platform']));
            if($platform === null) throw new BadRequestException(self::CREATE_ERROR_UNKNOWN_PLATFORM);
            // Duplicate test
            $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')
                ->findOneBy(array('name' => $taskResult['name'], 'version' => $taskResult['version']));
            if($firmware !== null) throw new BadRequestException(self::CREATE_ERROR_DUPLICATE);
            // Persistence
            $firmware = new Firmware();
            $firmware->name = $taskResult['name'];
            $firmware->version = $taskResult['version'];
            $firmware->description = $taskResult['description'];
            $firmware->changelog = '';
            $firmware->platform = $platform;
            $platform->addFirmwareRevision($firmware);
            // Set this firmware as default if there isn't a default yet
            if($platform->defaultFirmwareRevision === null) {
                $platform->defaultFirmwareRevision = $firmware;
            }
                $this->em->persist($firmware);
                $this->em->flush();
        } catch(OptimisticLockException|ORMException $e) {
            throw new SystemException($e);
        }
        $platform->registerFirmwareFile(
            $firmware,
            sprintf('%s/%s/%s', DATA_PATH, TasksService::UPLOAD_PATH, $task->params['path']));
        // Remove upload verification task
        $this->tasksService->deleteTask($user, $task->getId());
        $this->logger->log(sprintf('Firmware revision %s for platform %s added', $firmware->version, $platform->name), LogResource::PLATFORMS, $platform->getId());
        return $firmware;
    }

    /**
     * Sets the default firmware revision for a specific platform.
     *
     * @param int $id ID of the platform to update
     * @param int $defaultFirmwareRevision New default firmware revision ID
     * @throws SystemException
     */
    public function updateFirmware(int $id, int $defaultFirmwareRevision): Platform
    {
        try {
            $platform = $this->em->getRepository('HoneySens\app\models\entities\Platform')->find($id);
            if($platform === null) throw new NotFoundException();
            $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($defaultFirmwareRevision);
            if($firmware === null) throw new NotFoundException();
            $platform->defaultFirmwareRevision = $firmware;
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Default firmware revision for platform %s set to %s', $platform->name, $firmware->version), LogResource::PLATFORMS, $platform->getId());
        return $platform;
    }

    /**
     * Removes a specific firmware revision.
     *
     * @param int $id Firmware revision ID to delete
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function deleteFirmware(int $id): void {
        try {
            $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
            if ($firmware === null) throw new NotFoundException();
            $platform = $firmware->platform;
            // Don't remove the default firmware revision for this platform
            if ($platform->defaultFirmwareRevision === $firmware) throw new BadRequestException();
            // In case this revision is set as target firmware on some sensors, reset those back to their default revision
            $qb = $this->em->createQueryBuilder();
            $qb->select('s')->from('HoneySens\app\models\entities\Sensor', 's')
                ->where('s.firmware = :firmware')
                ->setParameter('firmware', $firmware);
            foreach ($qb->getQuery()->getResult() as $sensor) {
                $sensor->firmware = null;
            }
            $platform->unregisterFirmwareFile($firmware);
            $platform->removeFirmwareRevision($firmware);
            $this->em->remove($firmware);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Firmware revision %s of platform %s deleted', $firmware->version, $platform->name), LogResource::PLATFORMS, $platform->getId());
    }

    /**
     * Locates download details for the given firmware ID.
     * Returns a tuple [<local_firmware_path>, <file_name>].
     *
     * @param int $firmwareId Firmware revision identifier
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function getFirmwareDownload(int $firmwareId): array {
        try {
            $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($firmwareId);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($firmware === null) throw new NotFoundException();
        $platform = $firmware->platform;
        $firmwarePath = $platform->obtainFirmware($firmware);
        $downloadName = sprintf('%s-%s.tar.gz', preg_replace('/\s+/', '-', strtolower($firmware->name)), preg_replace('/\s+/', '-', strtolower($firmware->version)));
        if($firmwarePath === null) throw new BadRequestException();
        return [$firmwarePath, $downloadName];
    }

    /**
     * Locates download details for he current default firmware for a given platform ID.
     * Returns a tuple [<local_firmware_path>, <file_name>].
     *
     * @param int $platformId Platform identifier
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function downloadCurrentFirmwareForPlatform(int $platformId): array {
        try {
            $platform = $this->em->getRepository('HoneySens\app\models\entities\Platform')->find($platformId);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($platform === null) throw new NotFoundException();
        $firmware = $platform->defaultFirmwareRevision;
        if($firmware === null) throw new NotFoundException();
        return $this->getFirmwareDownload($firmware->getId());
    }
}
