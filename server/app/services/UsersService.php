<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\controllers\Users;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\Utils;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Respect\Validation\Validator as V;

class UsersService {

    private ConfigParser $config;
    private EntityManager $em;
    private LogService $logger;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger) {
        $this->config = $config;
        $this->em= $em;
        $this->logger = $logger;
    }

    /**
     * Fetches Users from the DB by various criteria:
     * - userID: return only users that belong to divisions this user is allowed to access
     * - id: return the user with the given id
     * If no criteria are given, all users are returned.
     *
     * @param array $criteria
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get($criteria) {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('HoneySens\app\models\entities\User', 'u');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('u.divisions', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('u.id = :id')
                ->setParameter('id', $criteria['id']);
            try {
                return $this->getStateWithPermissionConfig($qb->getQuery()->getSingleResult());
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
        } else {
            $users = array();
            foreach($qb->getQuery()->getResult() as $user) {
                $users[] = $this->getStateWithPermissionConfig($user);
            }
            return $users;
        }
    }

    /**
     * Creates and persists a new User object.
     * The following parameters are required:
     * - name: Login name of the user
     * - email: E-Mail address
     * - password: User password
     * - role: User role, determining his permissions (0 to 3)
     * - notifyOnSystemState: Whether this user should receive system state notifications (bool)
     *
     * @param array $data
     * @return User
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function create($data) {
        // Validation
        V::arrayType()
            ->key('name', V::alnum()->length(1, 255))
            ->key('domain', V::intVal()->between(0, 1))
            ->key('fullName', V::stringType()->length(1, 255), false)
            ->key('email', Utils::emailValidator())
            ->key('role', V::intVal()->between(1, 3))
            ->key('notifyOnSystemState', V::boolVal())
            ->key('requirePasswordChange', V::boolVal())
            ->check($data);
        // Password is optional if another domain than the local one is used
        V::key('password', V::stringType()->length(6, 255), $data['domain'] == User::DOMAIN_LOCAL)
            ->check($data);
        // Name duplication check
        if($this->getUserByName($data['name']) != null) throw new BadRequestException(Users::ERROR_DUPLICATE);
        // Persistence
        $user = new User();
        $user->setName($data['name'])
            ->setDomain($data['domain'])
            ->setEmail($data['email'])
            ->setRole($data['role'])
            ->setNotifyOnSystemState($data['notifyOnSystemState'])
            ->setRequirePasswordChange($data['requirePasswordChange']);
        if(V::key('password')->validate($data))
            $user->setPassword($data['password']);
        if(V::key('fullName')->validate($data)) $user->setFullName($data['fullName']);
        $this->em->persist($user);
        $this->em->flush();
        $this->logger->log(sprintf('User %s (ID %d) created', $user->getName(), $user->getId()), LogResource::USERS, $user->getId());
        return $user;
    }

    /**
     * Updates an existing User object.
     * The following parameters are required:
     * - name: Login name of the user
     * - email: E-Mail address
     * - password: User password
     * - role: User role, determining his permissions (0 to 3)
     * - notifyOnSystemState: Whether this user should receive system state notifications (bool)     *
     *
     * @param int $id
     * @param array $data
     * @return User
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function update($id, $data) {
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('name', V::alnum()->length(1, 255))
            ->key('domain', V::intVal()->between(0, 1))
            ->key('fullName', V::stringType()->length(1, 255), false)
            ->key('email', Utils::emailValidator())
            ->key('role', V::intVal()->between(1, 3))
            ->key('notifyOnSystemState', V::boolVal())
            ->key('requirePasswordChange', V::boolVal())
            ->check($data);
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($id);
        V::objectType()->check($user);
        // Only require a password if the user didn't have one previously (e.g. due to it being created as an LDAP account)
        $requirePassword = $data['domain'] == User::DOMAIN_LOCAL && $user->getPassword() == null && $user->getLegacyPassword() == null;
        V::key('password', V::stringType()->length(6, 255), $requirePassword)
            ->check($data);
        // Name duplication check
        $duplicate = $this->getUserByName($data['name']);
        if($duplicate != null && $duplicate->getId() != $user->getId())
            throw new BadRequestException(Users::ERROR_DUPLICATE);
        // Persistence
        $user->setName($data['name'])
            ->setDomain($data['domain'])
            ->setEmail($data['email'])
            ->setNotifyOnSystemState($data['notifyOnSystemState'])
            ->setRequirePasswordChange($data['requirePasswordChange']);
        // Set role, but force the first user to be an admin
        if($user->getId() != 1) $user->setRole($data['role']);
        // Set optional password
        if(V::key('password')->validate($data)) {
            $user->setPassword($data['password'])
                ->setLegacyPassword(null);
        };
        // Set optional full name
        if(V::key('fullName')->validate($data)) $user->setFullName($data['fullName']);
        else $user->setFullName(null);
        $this->em->flush();
        $this->logger->log(sprintf('User %s (ID %d) updated', $user->getName(), $user->getId()), LogResource::USERS, $user->getId());
        return $user;
    }

    /**
     * Updates the user the current session belongs to, e.g. allows logged-in users to change their own password.
     *
     * @param array $data
     * @return User
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function updateSelf($data) {
        $sessionUser = $this->em->getRepository('HoneySens\app\models\entities\User')->find($_SESSION['user']['id']);
        if($sessionUser == null || !$sessionUser->getRequirePasswordChange()) throw new ForbiddenException();
        // Validation
        V::arrayType()->key('password', V::stringType()->length(6, 255))->check($data);
        if($sessionUser->getPassword() != null && password_verify($data['password'], $sessionUser->getPassword())) throw new BadRequestException(Users::ERROR_REQUIRE_PASSWORD_CHANGE);
        // Persistence
        $sessionUser->setPassword($data['password'])
            ->setLegacyPassword(null)
            ->setRequirePasswordChange(false);
        $this->em->flush();
        $this->logger->log(sprintf('Password of user %s (ID %d) updated', $sessionUser->getName(), $sessionUser->getId()), LogResource::USERS, $sessionUser->getId());
        return $sessionUser;
    }

    public function delete($id) {
        // Validation: never remove the user with ID 1, who is always an admin
        V::intVal()->not(V::equals(1))->check($id);
        // Persistence
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($id);
        V::objectType()->check($user);
        $uid = $user->getId();
        $this->em->remove($user);
        $this->em->flush();
        $this->logger->log(sprintf('User %s (ID %d) deleted', $user->getName(), $uid), LogResource::USERS, $uid);
    }

    /**
     * Returns the state of a user as array and mixes in additional permission configuration, such as role restrictions.
     * This is meant as an intermediate solution on the way to custom role/permission definitions.
     *
     * @param User $user
     * @return array
     */
    public function getStateWithPermissionConfig(User $user) {
        $state = $user->getState();
        // Incorporate role restrictions
        if($user->getRole() == User::ROLE_MANAGER) {
            if($this->config->getBoolean('misc', 'prevent_event_deletion_by_managers'))
                $state['permissions']['events'] = array_values(array_diff($state['permissions']['events'], ['delete']));
            if($this->config->getBoolean('misc', 'prevent_sensor_deletion_by_managers'))
                $state['permissions']['sensors'] = array_values(array_diff($state['permissions']['sensors'], ['delete']));
        }
        // Enable API logging if the module is enabled and $user is an administrator
        if($user->getRole() == User::ROLE_ADMIN && $this->logger->isEnabled()) {
            $state['permissions']['logs'] = array('get');
        }
        return $state;
    }

    private function getUserByName($name) {
        return $this->em->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $name));
    }
}
