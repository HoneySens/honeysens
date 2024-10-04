<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\TaskStatus;
use HoneySens\app\models\constants\TaskType;

/**
 * This class represents a specific task that is performed by an external task queue, such as celery.
 * Tasks are owned and fully controlled by a user, although administrators can request and modify all tasks.
 */
#[Entity]
#[Table(name: "tasks")]
class Task {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private $id;

    /**
     * The user who submitted this task.
     */
    #[ManyToOne(targetEntity: User::class, inversedBy: "tasks")]
    public User $user;

    /**
     * The type of task that should be executed, currently amongst a set of hardcoded values.
     */
    #[Column()]
    public TaskType $type;

    /**
     * The status field determines whether this task is currently scheduled, running or completed.
     */
    #[Column()]
    public TaskStatus $status = TaskStatus::SCHEDULED;

    /**
     * Arbitrary task-specific parameters to hand over to the task executor.
     */
    #[Column(type: Types::JSON, nullable: true)]
    public ?array $params = array();

    /**
     * After successful execution, this field holds arbitrary task-specific result data.
     */
    #[Column(type: Types::JSON, nullable: true)]
    public ?array $result = array();

    public function getId(): int {
        return $this->id;
    }

    public function getState(): array {
        return array(
            'id' => $this->getId(),
            'user' => $this->user?->getId(),
            'type' => $this->type->value,
            'status' => $this->status->value,
            'params' => $this->params,
            'result' => $this->result
        );
    }
}
