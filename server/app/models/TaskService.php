<?php
namespace HoneySens\app\models;

use Celery;
use CeleryException;
use CeleryPublishException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class TaskService {

    const PRIORITY_LOW = 'low';
    const PRIORITY_HIGH = 'high';

    private $config;
    private $em;
    private $queue_high;
    private $queue_low;
    private $services;

    public function __construct($services, ConfigParser $config, EntityManager $em) {
        $this->services = $services;
        $broker_host = getenv('HS_BROKER_HOST');
        $broker_port = getenv('HS_BROKER_PORT');
        $this->queue_high = new Celery($broker_host, null, null, null, self::PRIORITY_HIGH, self::PRIORITY_HIGH, $broker_port, 'redis');
        $this->queue_low = new Celery($broker_host, null, null, null, self::PRIORITY_LOW, self::PRIORITY_LOW, $broker_port, 'redis');
        $this->config = $config;
        $this->em = $em;
    }

    public function isAvailable() {
        # TODO celery-php doesn't support a ping natively, have a look into predis
        return true;
    }

    /**
     * Registers a new task by persisting it into the database and notifying the task queue
     *
     * @param User $user
     * @param int $type
     * @param array $params
     * @return Task
     * @throws OptimisticLockException
     * @throws CeleryException
     * @throws CeleryPublishException
     * @throws Exception
     */
    public function enqueue($user, $type, $params) {
        // Persist
        $task = new Task();
        $task->setType($type)->setParams($params);
        if($user) $user->addTask($task); // Unassociated tasks are allowed
        $this->em->persist($task);
        $this->em->flush();
        // Send to job queue
        switch ($task->getType()) {
            case Task::TYPE_EMAIL_EMITTER:
                $this->queue_low->PostTask('processor.tasks.emit_email', array($task->getId()));
                break;
            case Task::TYPE_EVENT_EXTRACTOR:
                $this->queue_low->PostTask('processor.tasks.extract_events', array($task->getId()));
                break;
            case Task::TYPE_EVENT_FORWARDER:
                $this->queue_high->PostTask('processor.tasks.forward_events', array($task->getId()));
                break;
            case Task::TYPE_REGISTRY_MANAGER:
                $this->queue_low->PostTask('processor.tasks.upload_to_registry', array($task->getId()));
                break;
            case Task::TYPE_SENSORCFG_CREATOR:
                $this->queue_low->PostTask('processor.tasks.create_sensor_config', array($task->getId()));
                break;
            case Task::TYPE_UPLOAD_VERIFIER:
                $this->queue_low->PostTask('processor.tasks.verify_upload', array($task->getId()));
                break;
            default:
                throw new Exception('Unknown task type ' . $task->getType());
        }
        return $task;
    }
}