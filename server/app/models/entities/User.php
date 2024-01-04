<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User {

    const ROLE_GUEST = 0;
    const ROLE_OBSERVER = 1;
    const ROLE_MANAGER = 2;
    const ROLE_ADMIN = 3;

    const DOMAIN_LOCAL = 0;
    const DOMAIN_LDAP = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The E-Mail address that belongs to this user
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * Hashed password of this user (using bcrypt).
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $password;

    /**
     * SHA1-hashed password used prior to upgrading to bcrypt hashes.
     * If this is null, this user's password uses the new hashing scheme.
     * This attribute will be removed in future versions.
     *
     * @deprecated
     * @ORM\Column(type="string", nullable=true)
     */
    protected $legacyPassword;

    /**
     * If true, this user will be prompted for a password change after the next login.
     *
     * @ORM\Column(type="boolean")
     */
    protected $requirePasswordChange = false;

    /**
     * The domain that this user is authenticated against
     *
     * @ORM\Column(type="integer")
     */
    protected $domain = self::DOMAIN_LOCAL;

    /**
     * Full name or description of this user
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $fullName;

    /**
     * @ORM\Column(type="integer")
     */
    protected $role;

    /**
     * @ORM\ManyToMany(targetEntity="HoneySens\app\models\entities\Division", inversedBy="users")
     * @ORM\JoinTable(name="users_divisions")
     */
    protected $divisions;

    /**
     * This reference is only made to ensure cascading events in case a user is removed.
     * It's not made public as an attribute of the entity.
     *
     * @ORM\OneToMany(targetEntity="HoneySens\app\models\entities\IncidentContact", mappedBy="user", cascade={"remove"})
     */
    protected $incidentContacts;

    /**
     * References the tasks this user has submitted.
     *
     * @ORM\OneToMany(targetEntity="HoneySens\app\models\entities\Task", mappedBy="user", cascade={"remove"})
     */
    protected $tasks;

    /**
     * Whether to send system state notifications (e.g. high load, CA expiration) to the E-Mail address of this user.
     *
     * @ORM\Column(type="boolean")
     */
    protected $notifyOnSystemState = false;

    public function __construct() {
        $this->divisions = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return User
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set domain
     *
     * @param integer $domain
     * @return User
     */
    public function setDomain($domain) {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get domain
     *
     * @return integer
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Set the full name or description
     *
     * @param string $fullName
     * @return $this
     */
    public function setFullName($fullName) {
        $this->fullName = $fullName == null ? null : $fullName;
        return $this;
    }

    /**
     * Get full name or description
     *
     * @return string
     */
    public function getFullName() {
        return $this->fullName;
    }

    /**
     * Set E-Mail address
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * Get E-Mail address
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password) {
        $this->password = $password == null ? null : password_hash($password, PASSWORD_BCRYPT);
        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set the legacy password, which means a SHA1 hash.
     *
     * @param string $password
     * @return $this
     */
    public function setLegacyPassword($password) {
        $this->legacyPassword = $password == null ? null : sha1($password);
        return $this;
    }

    /**
     * Get the legacy password, if set. Null otherwise.
     *
     * @return mixed
     */
    public function getLegacyPassword() {
        return $this->legacyPassword;
    }

    /**
     * Sets whether a password change is required upon the next login.
     *
     * @param boolean $require
     * @return $this
     */
    public function setRequirePasswordChange($require) {
        $this->requirePasswordChange = $require;
        return $this;
    }

    /**
     * Whether a password change is required upon the next login.
     *
     * @return boolean
     */
    public function getRequirePasswordChange() {
        return $this->requirePasswordChange;
    }

    /**
     * Set role
     *
     * @param integer $role
     * @return User
     */
    public function setRole($role) {
        $this->role = $role;
        return $this;
    }

    /**
     * Get role
     *
     * @return integer
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * Add this user to an existing division
     *
     * @param Division $division
     * @return $this
     */
    public function addToDivision(Division $division) {
        $division->addUser($this);
        $this->divisions[] = $division;
        return $this;
    }

    /**
     * Remove this user from a division
     *
     * @param Division $division
     * @return $this
     */
    public function removeFromDivision(Division $division) {
        $division->removeUser($this);
        $this->divisions->removeElement($division);
        return $this;
    }

    /**
     * Register a task with this user.
     *
     * @param Task $task
     * @return $this
     */
    public function addTask(Task $task) {
        $this->tasks[] = $task;
        $task->setUser($this);
        return $this;
    }

    /**
     * Disassociates a task from this user.
     *
     * @param Task $task
     * @return $this
     */
    public function removeTask(Task $task) {
        $this->tasks->removeElement($task);
        $task->setUser(null);
        return $this;
    }

    /**
     * Get all tasks associated with this user.
     *
     * @return ArrayCollection
     */
    public function getTasks() {
        return $this->tasks;
    }

    /**
     * Enable or disable notifications in case of CA expiration or high system load for this user.
     *
     * @param boolean $notify
     * @return $this
     */
    public function setNotifyOnSystemState($notify) {
        $this->notifyOnSystemState = $notify;
        return $this;
    }

    /**
     * Whether this user receives notifications about the system state.
     *
     * @return boolean
     */
    public function getNotifyOnSystemState() {
        return $this->notifyOnSystemState;
    }

    /**
     * Returns an array of controller permissions for this user
     * of the form array('<CONTROLLER>' => array('<METHOD>', ...), ...)
     *
     * @return array
     */
    public function getPermissions() {
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
            case $this::ROLE_ADMIN:
                array_push($permissions['divisions'], 'create', 'update', 'delete');
                array_push($permissions['users'], 'create', 'update', 'delete');
                array_push($permissions['settings'], 'create', 'update', 'delete');
                array_push($permissions['platforms'], 'create', 'update', 'delete');
                array_push($permissions['services'], 'create', 'update', 'delete');
            case $this::ROLE_MANAGER:
                array_push($permissions['certs'], 'create', 'delete');
                array_push($permissions['events'], 'update', 'archive', 'delete');
                array_push($permissions['eventfilters'], 'create', 'update', 'delete');
                array_push($permissions['platforms'], 'download');
                array_push($permissions['sensors'], 'create', 'update', 'delete', 'downloadConfig');
                array_push($permissions['users'], 'all', 'get');
                array_push($permissions['contacts'], 'create', 'update', 'delete');
            case $this::ROLE_OBSERVER:
                array_push($permissions['certs'], 'all', 'get');
                array_push($permissions['eventdetails'], 'get');
                array_push($permissions['events'], 'all', 'get', 'getByLastID');
                array_push($permissions['eventfilters'], 'get');
                array_push($permissions['sensors'], 'all', 'get');
                array_push($permissions['divisions'], 'all', 'get');
                array_push($permissions['contacts'], 'all', 'get');
                array_push($permissions['platforms'], 'all', 'get');
                array_push($permissions['sensorstatus'], 'get');
                array_push($permissions['services'], 'get');
                array_push($permissions['settings'], 'all', 'get');
                array_push($permissions['stats'], 'get');
                array_push($permissions['tasks'], 'get', 'create', 'update', 'delete');
                array_push($permissions['users'], 'updateSelf');
            case $this::ROLE_GUEST:
                array_push($permissions['state'], 'get');
                break;
        }
        return $permissions;
    }

    public function getState() {
        $divisions = array();
        foreach($this->divisions as $division) {
            $divisions[] = $division->getId();
        }
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'domain' => $this->getDomain(),
            'full_name' => $this->getFullName(),
            'email' => $this->getEmail(),
            'require_password_change' => $this->getRequirePasswordChange(),
            'role' => $this->getRole(),
            'permissions' => $this->getPermissions(),
            'divisions' => $divisions,
            'notify_on_system_state' => $this->getNotifyOnSystemState()
        );
    }
}
