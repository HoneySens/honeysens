<?php
namespace HoneySens\app\models;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Pheanstalk\Pheanstalk;

class TaskService {

    const BEANSTALK_TUBE = 'honeysens';

    private $config = null;
    private $em = null;
    private $pheanstalk = null;

    public function __construct(ConfigParser $config, EntityManager $em) {
        $this->config = $config;
        $this->em = $em;
        $this->pheanstalk = new Pheanstalk($config['beanstalkd']['host'], $config['beanstalkd']['port']);
    }

    public function isAvailable() {
        return $this->pheanstalk->getConnection()->isServiceListening();
    }

    /**
     * Registers a new task by persisting it into the database and notifying the job manager (beanstalkd).
     *
     * @param User $user
     * @param int $type
     * @param array $params
     * @return Task
     * @throws OptimisticLockException
     */
    public function enqueue(User $user, $type, $params) {
        // Persist
        $task = new Task();
        $task->setType($type)->setParams($params);
        $user->addTask($task);
        $this->em->persist($task);
        $this->em->flush();
        // Notify job manager
        $this->pheanstalk->useTube(self::BEANSTALK_TUBE)->put(json_encode($task->getState()));
        return $task;
    }
}