<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\AuthDomain;
use HoneySens\app\models\constants\UserRole;

#[Entity]
#[Table(name: "users")]
class User {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Short user login name.
     */
    #[Column(type: Types::STRING)]
    public string $name;

    /**
     * An e-mail address that belongs to this user.
     */
    #[Column(type: Types::STRING)]
    public string $email;

    /**
     * Hashed user password (uses bcrypt).
     */
    #[Column(type: Types::STRING, nullable: true)]
    private ?string $password;

    /**
     * SHA1-hashed password used prior to upgrading to bcrypt hashes.
     * If this is null, this user's password uses the new hashing scheme.
     * This attribute will be removed in future versions.
     *
     * @deprecated
     */
    #[Column(type: Types::STRING, nullable: true)]
    private ?string $legacyPassword;

    /**
     * If true, this user will be prompted for a password change after the next login.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $requirePasswordChange = false;

    /**
     * The domain this user is authenticated against.
     */
    #[Column()]
    public AuthDomain $domain = AuthDomain::LOCAL;

    /**
     * Full name or description of this user.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $fullName;

    /**
     * A user's role determines the set of granted permissions
     * when interacting with the API.
     */
    #[Column()]
    public UserRole $role;

    #[ManyToMany(targetEntity: Division::class, inversedBy: "users")]
    #[JoinTable(name: "users_divisions")]
    private Collection $divisions;

    /**
     * This reference is only made to ensure cascading events in case a user is removed.
     * It's not made public as an attribute of the entity.
     */
    #[OneToMany(mappedBy: "user", targetEntity: IncidentContact::class, cascade: ["remove"])]
    private Collection $incidentContacts;

    /**
     * References the tasks this user has submitted.
     */
    #[OneToMany(mappedBy: "user", targetEntity: Task::class, cascade: ["remove"])]
    private Collection $tasks;

    /**
     * Whether to send system state notifications (e.g. high load, CA expiration) to the E-Mail address of this user.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $notifyOnSystemState = false;

    public function __construct() {
        $this->divisions = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Sets a new password for this user.
     */
    public function setPassword(?string $password): void {
        $this->password = $password === null ? null : password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Returns the hashed user password, if one is set. Null otherwise.
     */
    public function getHashedPassword(): ?string {
        return $this->password;
    }

    /**
     * Sets or unsets the legacy password, which is a SHA1 hash.
     */
    public function setLegacyPassword(?string $password): void {
        $this->legacyPassword = $password == null ? null : sha1($password);
    }

    /**
     * Returns the legacy password, if set. Null otherwise.
     */
    public function getLegacyPassword(): ?string {
        return $this->legacyPassword;
    }

    /**
     * Adds this user to an existing division.
     */
    public function addToDivision(Division $division): void {
        $division->addUser($this);
        $this->divisions[] = $division;
    }

    /**
     * Removes this user from a division.
     */
    public function removeFromDivision(Division $division): void {
        $division->removeUser($this);
        $this->divisions->removeElement($division);
    }

    /**
     * Associates a task with this user.
     */
    public function addTask(Task $task): void {
        $this->tasks[] = $task;
        $task->setUser($this);
    }

    /**
     * Returns an array of service permissions for this user
     * in the form array('<SERVICE>' => array('<OPERATION>', ...), ...)
     */
    public function getPermissions(): array {
        $permissions = array('certs' => array(),
            'eventdetails' => array(),
            'events' => array(),
            'eventfilters' => array(),
            'logs' => array(),
            'sensors' => array(),
            'sensorstatus' => array(),
            'divisions' => array(),
            'users' => array(),
            'contacts' => array(),
            'platforms' => array(),
            'services' => array(),
            'settings' => array(),
            'stats' => array(),
            'state' => array(),
            'tasks' => array());
        switch($this->role) {
            case UserRole::ADMIN:
                array_push($permissions['divisions'], 'create', 'update', 'delete');
                array_push($permissions['users'], 'create', 'update', 'delete');
                array_push($permissions['settings'], 'create', 'update', 'delete');
                array_push($permissions['platforms'], 'create', 'update', 'delete');
                array_push($permissions['services'], 'create', 'update', 'delete');
                array_push($permissions['tasks'], 'upload');
            case UserRole::MANAGER:
                array_push($permissions['certs'], 'create', 'delete');
                array_push($permissions['events'], 'update', 'archive', 'delete');
                array_push($permissions['eventfilters'], 'create', 'update', 'delete');
                array_push($permissions['platforms'], 'download');
                array_push($permissions['sensors'], 'create', 'update', 'delete', 'downloadConfig');
                array_push($permissions['users'], 'get');
                array_push($permissions['contacts'], 'create', 'update', 'delete');
            case UserRole::OBSERVER:
                array_push($permissions['certs'], 'get');
                array_push($permissions['eventdetails'], 'get');
                array_push($permissions['events'], 'get', 'getByLastID');
                array_push($permissions['eventfilters'], 'get');
                array_push($permissions['sensors'], 'get');
                array_push($permissions['divisions'], 'get');
                array_push($permissions['contacts'], 'get');
                array_push($permissions['platforms'], 'get');
                array_push($permissions['sensorstatus'], 'get');
                array_push($permissions['services'], 'get');
                array_push($permissions['settings'], 'get');
                array_push($permissions['stats'], 'get');
                array_push($permissions['tasks'], 'get', 'create', 'update', 'delete');
                array_push($permissions['users'], 'updateSelf');
            case UserRole::GUEST:
                array_push($permissions['state'], 'get');
                break;
        }
        return $permissions;
    }

    public function getState(): array {
        $divisions = array();
        foreach($this->divisions as $division) {
            $divisions[] = $division->getId();
        }
        return array(
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'domain' => $this->domain->value,
            'full_name' => $this->fullName ?? null,
            'email' => $this->email ?? null,
            'require_password_change' => $this->requirePasswordChange,
            'role' => $this->role->value,
            'permissions' => $this->getPermissions(),
            'divisions' => $divisions,
            'notify_on_system_state' => $this->notifyOnSystemState
        );
    }
}
