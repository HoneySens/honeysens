<?php
namespace HoneySens\app\adapters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use Predis;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\Drivers\PredisRedisDriver;
use Smuuf\CeleryForPhp\TaskSignature;

class TaskAdapter {

    const PRIORITY_LOW = 'low';
    const PRIORITY_HIGH = 'high';

    private $broker;
    private $broker_host;
    private $broker_port;
    private $celery;
    private $em;

    public function __construct(EntityManager $em) {
        $this->broker_host = getenv('HS_BROKER_HOST');
        $this->broker_port = getenv('HS_BROKER_PORT');
        $this->broker = new Predis\Client([
            'scheme' => 'tcp',
            'host' => $this->broker_host,
            'port' => $this->broker_port
        ]);
        $driver = new PredisRedisDriver($this->broker);
        $this->celery = new Celery(new RedisBroker($driver), new RedisBackend($driver));
        $this->em = $em;
    }

    /**
     * Registers a new task by persisting it into the database and notifying the task queue
     *
     * @param ?User $user
     * @param int $type
     * @param array $params
     * @return Task
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function enqueue(?User $user, $type, $params): Task {
        // Persist
        $task = new Task();
        $task->setType($type)->setParams($params);
        if($user) $user->addTask($task); // Unassociated tasks are allowed
        $this->em->persist($task);
        $this->em->flush();
        // Prepare Celery task
        switch ($task->getType()) {
            case Task::TYPE_EMAIL_EMITTER:
                $taskSig = new TaskSignature('processor.tasks.emit_email', self::PRIORITY_LOW, array($task->getId()));
                break;
            case Task::TYPE_EVENT_EXTRACTOR:
                $taskSig = new TaskSignature('processor.tasks.extract_events', self::PRIORITY_LOW, array($task->getId()));
                break;
            case Task::TYPE_EVENT_FORWARDER:
                $taskSig = new TaskSignature('processor.tasks.forward_events', self::PRIORITY_HIGH, array($task->getId()));
                break;
            case Task::TYPE_REGISTRY_MANAGER:
                $taskSig = new TaskSignature('processor.tasks.upload_to_registry', self::PRIORITY_LOW, array($task->getId()));
                break;
            case Task::TYPE_SENSORCFG_CREATOR:
                $taskSig = new TaskSignature('processor.tasks.create_sensor_config', self::PRIORITY_LOW, array($task->getId()));
                break;
            case Task::TYPE_UPLOAD_VERIFIER:
                $taskSig = new TaskSignature('processor.tasks.verify_upload', self::PRIORITY_LOW, array($task->getId()));
                break;
            default:
                throw new Exception('Unknown task type ' . $task->getType());
        }
        // Send to job queue
        $this->celery->sendTask($taskSig);
        return $task;
    }

    /**
     * Queries the broker and returns the aggregated length of the low and high priority queues.
     *
     * @return int
     */
    public function getQueueLength() {
        return $this->broker->llen("low") + $this->broker->llen("high");
    }
}
