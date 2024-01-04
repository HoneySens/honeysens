<?php
namespace HoneySens\app\models\entities;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Task
 *
 * This class represents a specific task that is performed by an external task queue, such as celery.
 * Tasks are owned and fully controlled by a user, although administrators can request and modify all tasks.
 *
 * @ORM\Entity
 * @ORM\Table(name="tasks")
 * @package HoneySens\app\models\entities
 */
class Task {

    const STATUS_SCHEDULED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_DONE = 2;
    const STATUS_ERROR = 3;

    const TYPE_SENSORCFG_CREATOR = 0;
    const TYPE_UPLOAD_VERIFIER = 1;
    const TYPE_REGISTRY_MANAGER = 2;
    const TYPE_EVENT_EXTRACTOR = 3;
    const TYPE_EVENT_FORWARDER = 4;
    const TYPE_EMAIL_EMITTER = 6;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * The user who submitted this task.
     *
     * @ORM\ManyToOne(targetEntity="HoneySens\app\models\entities\User", inversedBy="tasks")
     */
    protected $user;

    /**
     * The type of task that should be executed, currently amongst a set of hardcoded values.
     *
     * @ORM\Column(type="integer")
     */
    protected $type;

    /**
     * The status flag determines whether this task is currently scheduled, running or completed.
     *
     * @ORM\Column(type="integer")
     */
    protected $status = self::STATUS_SCHEDULED;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected ?array $params = array();

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected ?array $result = array();

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param User|null $user
     * @return $this
     */
    public function setUser(User $user = null) {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param ?array $params
     * @return $this
     */
    public function setParams($params) {
        $this->params = $params;
        return $this;
    }

    /**
     * @return ?array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * @param ?array $result
     * @return $this
     */
    public function setResult($result) {
        $this->result = $result;
        return $this;
    }

    /**
     * @return ?array
     */
    public function getResult() {
        return $this->result;
    }

    public function getState() {
        return array(
            'id' => $this->getId(),
            'user' => $this->getUser() ? $this->getUser()->getId() : null,
            'type' => $this->getType(),
            'status' => $this->getStatus(),
            'params' => $this->getParams(),
            'result' => $this->getResult()
        );
    }
}