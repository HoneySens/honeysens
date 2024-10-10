<?php
namespace HoneySens\app\adapters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use HoneySens\app\models\constants\TaskPriority;
use HoneySens\app\models\constants\TaskType;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\SystemException;
use Predis;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\Drivers\PredisRedisDriver;
use Smuuf\CeleryForPhp\TaskSignature;

/**
 * Creates and enqueues tasks for asynchronous processing via an external task processing service.
 */
class TaskAdapter {

    private Predis\Client $broker;
    private string $broker_host;
    private string $broker_port;
    private Celery $celery;
    private EntityManager $em;

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
     * @param User|null $user User to associate this task with. Unassociated task are only visible for admins.
     * @param TaskType $type Worker type to use for this task.
     * @param array $params Additional parameters to hand over to the worker, format depends on selected task type.
     * @throws SystemException
     */
    public function enqueue(?User $user, TaskType $type, array $params): Task {
        // Persist
        $task = new Task();
        $task->type = $type;
        $task->params = $params;
        if($user) $user->addTask($task); // Unassociated tasks are allowed
        try {
            $this->em->persist($task);
            $this->em->flush();
        } catch (ORMException $e) {
            throw new SystemException($e);
        }
        // Prepare Celery task
        switch ($task->type) {
            case TaskType::EMAIL_EMITTER:
                $taskSig = new TaskSignature('processor.tasks.emit_email', TaskPriority::LOW->value, array($task->getId()));
                break;
            case TaskType::EVENT_EXTRACTOR:
                $taskSig = new TaskSignature('processor.tasks.extract_events', TaskPriority::LOW->value, array($task->getId()));
                break;
            case TaskType::EVENT_FORWARDER:
                $taskSig = new TaskSignature('processor.tasks.forward_events', TaskPriority::LOW->value, array($task->getId()));
                break;
            case TaskType::REGISTRY_MANAGER:
                $taskSig = new TaskSignature('processor.tasks.upload_to_registry', TaskPriority::LOW->value, array($task->getId()));
                break;
            case TaskType::SENSORCFG_CREATOR:
                $taskSig = new TaskSignature('processor.tasks.create_sensor_config', TaskPriority::LOW->value, array($task->getId()));
                break;
            case TaskType::UPLOAD_VERIFIER:
                $taskSig = new TaskSignature('processor.tasks.verify_upload', TaskPriority::LOW->value, array($task->getId()));
                break;
            default:
                throw new \Exception('Unknown task type ' . $task->type->value);
        }
        // Send to job queue
        $this->celery->sendTask($taskSig);
        return $task;
    }

    /**
     * Queries the broker and returns the aggregated length of the low and high priority queues.
     */
    public function getQueueLength(): int {
        return $this->broker->llen("low") + $this->broker->llen("high");
    }
}
