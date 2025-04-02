<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use HoneySens\app\controllers\Users;
use HoneySens\app\models\constants\AuthDomain;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class UsersService extends Service {

    private ConfigParser $config;
    private LogService $logger;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger) {
        parent::__construct($em);
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Fetches users from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific user to fetch
     * @throws NotFoundException
     */
    public function getUsers(User $user, ?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('HoneySens\app\models\entities\User', 'u');
        if($user->role !== UserRole::ADMIN) {
            $qb->join('u.divisions', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        if($id !== null) {
            $qb->andWhere('u.id = :id')
                ->setParameter('id', $id);
            try {
                return $this->getStateWithPermissionConfig($qb->getQuery()->getSingleResult());
            } catch (NoResultException|NonUniqueResultException) {
                throw new NotFoundException();
            }
        } else {
            $users = array();
            foreach ($qb->getQuery()->getResult() as $user) {
                $users[] = $this->getStateWithPermissionConfig($user);
            }
            return $users;
        }
    }

    /**
     * Creates and persists a new user.
     *
     * @param string $name Login name of the user
     * @param AuthDomain $domain Domain against which to authenticate the new user
     * @param string $email User's e-mail address
     * @param UserRole $role User role, determines granted permissions
     * @param bool $notifyOnSystemState Whether this user should receive system state notifications
     * @param bool $requirePasswordChange Whether this user is prompted to set a new password on next login
     * @param string|null $fullName Detailed user name, for display purposes only
     * @param string|null $password User password (only for Local authentication)
     * @throws BadRequestException
     * @throws SystemException
     */
    public function createUser(string     $name,
                               AuthDomain $domain,
                               string     $email,
                               UserRole   $role,
                               bool       $notifyOnSystemState,
                               bool       $requirePasswordChange,
                               ?string    $fullName = null,
                               ?string    $password = null): User {
        // Name duplication check
        if($this->getUserByName($name) !== null) throw new BadRequestException(Users::ERROR_DUPLICATE);
        // Persistence
        $user = new User();
        $user->name = $name;
        $user->domain = $domain;
        $user->email = $email;
        $user->requirePasswordChange = $requirePasswordChange;
        $user->notifyOnSystemState = $notifyOnSystemState;
        $user->role = $role;
        if($domain === AuthDomain::LOCAL) {
            if($password === null) throw new BadRequestException();
            $user->setPassword($password);
        }
        if($fullName !== null) $user->fullName = $fullName;
        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('User %s (ID %d) created', $user->name, $user->getId()), LogResource::USERS, $user->getId());
        return $user;
    }

    /**
     * Updates an existing user.
     *
     * @param int $id User ID to update
     * @param string $name Login name of the user
     * @param AuthDomain $domain Authentication domain (Local or LDAP)
     * @param string $email User's e-mail address
     * @param UserRole $role User role, determines granted permissions
     * @param bool $notifyOnSystemState Whether this user should receive system state notifications
     * @param bool $requirePasswordChange Whether this user is prompted to set a new password on next login
     * @param string|null $fullName Detailed user name, for display purposes only
     * @param string|null $password User password. If null, the existing password will be kept.
     * @throws BadRequestException
     * @throws SystemException
     */
    public function updateUser(int        $id,
                               string     $name,
                               AuthDomain $domain,
                               string     $email,
                               UserRole   $role,
                               bool       $notifyOnSystemState,
                               bool       $requirePasswordChange,
                               ?string    $fullName = null,
                               ?string    $password = null): User {
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($id);
        if($user === null) throw new BadRequestException();
        // Require a password if the user uses local authentication and hasn't one set yet
        if($domain === AuthDomain::LOCAL && $user->getHashedPassword() === null && $user->getLegacyPassword() === null && $password === null)
            throw new BadRequestException();
        // Name duplication check
        $duplicate = $this->getUserByName($name);
        if($duplicate !== null && $duplicate->getId() !== $user->getId())
            throw new BadRequestException(Users::ERROR_DUPLICATE);
        // Force the first user to remain an admin
        if($user->getId() === 1 && $role !== UserRole::ADMIN) throw new BadRequestException();
        // Persistence
        $user->name = $name;
        $user->fullName = $fullName;
        $user->email = $email;
        $user->domain = $domain;
        $user->requirePasswordChange = $requirePasswordChange;
        $user->notifyOnSystemState = $notifyOnSystemState;
        $user->role = $role;
        if($domain === AuthDomain::LOCAL) {
            if($password !== null) {
                $user->setPassword($password);
                $user->setLegacyPassword(null);
            }
        } else {
            $user->setPassword(null);
            $user->setLegacyPassword(null);
        }
        try {
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('User %s (ID %d) updated', $user->name, $user->getId()), LogResource::USERS, $user->getId());
        return $user;
    }

    /**
     * Updates just the password of a specific user, e.g. for self-service password changes.
     *
     * @param int $id User ID to update
     * @param string $password New password, has to be different than the existing one
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function updatePassword(int $id, string $password): User {
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($id);
        if($user === null || !$user->requirePasswordChange) throw new ForbiddenException();
        // Enforce an actual password change, don't accept the existing password as a new one
        if($user->getHashedPassword() !== null && password_verify($password, $user->getHashedPassword()))
            throw new BadRequestException(Users::ERROR_REQUIRE_PASSWORD_CHANGE);
        // Persistence
        $user->setPassword($password);
        $user->setLegacyPassword(null);
        $user->requirePasswordChange = false;
        try {
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Password of user %s (ID %d) updated', $user->name, $user->getId()), LogResource::USERS, $user->getId());
        return $user;
    }

    /**
     * Removes a user entity. It's not possible to remove the primary admin,
     * which is always the first account on any deployment.
     *
     * @param int $id User ID to delete
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function deleteUser(int $id): void {
        // Never remove the user with ID 1, which is the primary admin account
        if($id === 1) throw new ForbiddenException();
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($id);
        if($user === null) throw new BadRequestException();
        $uid = $user->getId();
        try {
            $this->em->remove($user);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('User %s (ID %d) deleted', $user->name, $uid), LogResource::USERS, $uid);
    }

    /**
     * Enriches the state of a user with additional permission configuration, such as role restrictions.
     * Returns the resulting state as array.
     *
     * @param User $user
     */
    public function getStateWithPermissionConfig(User $user): array {
        $state = $user->getState();
        // Incorporate role restrictions
        if($user->role === UserRole::MANAGER) {
            if($this->config->getBoolean('misc', 'prevent_event_deletion_by_managers'))
                $state['permissions']['events'] = array_values(array_diff($state['permissions']['events'], ['delete']));
            if($this->config->getBoolean('misc', 'prevent_sensor_deletion_by_managers'))
                $state['permissions']['sensors'] = array_values(array_diff($state['permissions']['sensors'], ['delete']));
        }
        // Enable API logging if the logging module is enabled and $user is an administrator
        if($user->role === UserRole::ADMIN && $this->logger->isEnabled())
            $state['permissions']['logs'] = array('get');
        return $state;
    }

    /**
     * Fetches a user by login/user name.
     *
     * @param string $name A user (login) name
     */
    private function getUserByName(string $name): ?User {
        return $this->em->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $name));
    }
}
