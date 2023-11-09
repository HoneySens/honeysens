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
use Predis;

class TaskService {

    const PRIORITY_LOW = 'low';
    const PRIORITY_HIGH = 'high';

    private $broker_host;
    private $broker_port;
    private $config;
    private $em;
    private $queue_high;
    private $queue_low;
    private $services;

    public function __construct($services, ConfigParser $config, EntityManager $em) {
        $this->services = $services;
        $this->broker_host = getenv('HS_BROKER_HOST');
        $this->broker_port = getenv('HS_BROKER_PORT');
        $this->queue_high = new Celery($this->broker_host, null, null, null, self::PRIORITY_HIGH, self::PRIORITY_HIGH, $this->broker_port, 'redis');
        $this->queue_low = new Celery($this->broker_host, null, null, null, self::PRIORITY_LOW, self::PRIORITY_LOW, $this->broker_port, 'redis');
        $this->config = $config;
        $this->em = $em;
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

    /**
     * Queries the broker and returns the aggregated length of the low and high priority queues.
     *
     * @return int
     */
    public function getQueueLength() {
        $broker = new Predis\Client([
            'scheme' => 'tcp',
            'host' => $this->broker_host,
            'port' => $this->broker_port
        ]);
        return $broker->llen("low") + $broker->llen("high");
    }
}
