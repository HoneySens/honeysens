<?php
namespace HoneySens\app\models\entities;

/**
 * @Entity
 * @Table(name="users")
 */
class User {
	
	const ROLE_GUEST = 0;
	const ROLE_OBSERVER = 1;
	const ROLE_MANAGER = 2;
	const ROLE_ADMIN = 3;

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue 
	 */
	protected $id;
	
	/**
	 * @Column(type="string")
	 */
	protected $name;

	/**
	 * The E-Mail address that belongs to this user
	 *
	 * @Column(type="string")
	 */
	protected $email;
	
	/**
	 * @Column(type="string")
	 */
	protected $password;
	
	/**
	 * @Column(type="integer")
	 */
	protected $role;

	/**
	 * @ManyToMany(targetEntity="HoneySens\app\models\entities\Division", inversedBy="users")
	 * @JoinTable(name="users_divisions")
	 */
	protected $divisions;

    /**
     * This reference is only made to ensure cascading events in case a user is removed.
     * It's not made publicly as an attribute of the entity.
     *
     * @OneToMany(targetEntity="HoneySens\app\models\entities\IncidentContact", mappedBy="user", cascade={"remove"})
     */
    protected $incidentContacts;

	public function __construct() {
		$this->divisions = new \Doctrine\Common\Collections\ArrayCollection();
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
		$this->password = $password;
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
			'sensors' => array(),
			'sensorstatus' => array(),
			'divisions' => array(),
			'users' => array(),
            'contacts' => array(),
			'platforms' => array(),
			'services' => array(),
			'settings' => array(),
            'stats' => array(),
            'state' => array());
		switch($this->role) {
			case $this::ROLE_ADMIN:
				array_push($permissions['divisions'], 'create', 'update', 'delete');
				array_push($permissions['users'], 'create', 'update', 'delete');
                array_push($permissions['settings'], 'create', 'update', 'delete');
                array_push($permissions['platforms'], 'create', 'update', 'delete');
                array_push($permissions['services'], 'create', 'update', 'delete');
			case $this::ROLE_MANAGER:
				array_push($permissions['certs'], 'create', 'delete');
				array_push($permissions['events'], 'update', 'delete');
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
            'email' => $this->getEmail(),
			'role' => $this->getRole(),
			'permissions' => $this->getPermissions(),
			'divisions' => $divisions
		);
	}
}