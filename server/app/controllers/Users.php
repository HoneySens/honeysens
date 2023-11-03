<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use HoneySens\app\models\Utils;
use Respect\Validation\Validator as V;

class Users extends RESTResource {

    const ERROR_DUPLICATE = 1;
    const ERROR_REQUIRE_PASSWORD_CHANGE = 2;

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/users(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Users($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $id;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
        });

        $app->post('/api/users', function() use ($app, $em, $services, $config) {
            $controller = new Users($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $userData = json_decode($request);
            $user = $controller->create($userData);
            echo json_encode($user->getState());
        });

        $app->put('/api/users/:id', function($id) use ($app, $em, $services, $config) {
            $controller = new Users($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $userData = json_decode($request);
            $user = $controller->update($id, $userData);
            echo json_encode($user->getState());
        });

        $app->put('/api/users/session', function() use ($app, $em, $services, $config) {
            $controller = new Users($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $userData = json_decode($request);
            $user = $controller->updateSelf($userData);
            echo json_encode($user->getState());
        });

        $app->delete('/api/users/:id', function($id) use ($app, $em, $services, $config) {
            $controller = new Users($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
        });
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
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')->from('HoneySens\app\models\entities\User', 'u');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('u.divisions', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('u.id = :id')
                ->setParameter('id', $criteria['id']);
            return $this->getStateWithPermissionConfig($qb->getQuery()->getSingleResult());
        } else {
            $users = array();
            foreach($qb->getQuery()->getResult() as $user) {
                $users[] = $this->getStateWithPermissionConfig($user);
            }
            return $users;
        }
    }

    /**
     * Returns the state of a user as array and mixes in additional permission configuration, such as role restrictions.
     * This is meant as an intermediate solution on the way to custom role/permission definitions.
     *
     * @param User $user
     * @return array
     */
    public function getStateWithPermissionConfig($user) {
        $state = $user->getState();
        // Incorporate role restrictions
        if($user->getRole() == User::ROLE_MANAGER) {
            if($this->getConfig()->getBoolean('misc', 'prevent_event_deletion_by_managers'))
                $state['permissions']['events'] = array_values(array_diff($state['permissions']['events'], ['delete']));
            if($this->getConfig()->getBoolean('misc', 'prevent_sensor_deletion_by_managers'))
                $state['permissions']['sensors'] = array_values(array_diff($state['permissions']['sensors'], ['delete']));
        }
        // Enable API logging if the module is enabled and $user is an administrator
        if($user->getRole() == User::ROLE_ADMIN &&
            $this->getServiceManager()->get(ServiceManager::SERVICE_LOG)->isEnabled()) {
            $state['permissions']['logs'] = array('get');
        }
        return $state;
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
     * @param stdClass $data
     * @return User
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function create($data) {
        $this->assureAllowed('create');
        // Validation
        V::objectType()
            ->attribute('name', V::alnum()->length(1, 255))
            ->attribute('domain', V::intVal()->between(0, 1))
            ->attribute('fullName', V::stringType()->length(1, 255), false)
            ->attribute('email', Utils::emailValidator())
            ->attribute('role', V::intVal()->between(1, 3))
            ->attribute('notifyOnSystemState', V::boolVal())
            ->attribute('requirePasswordChange', V::boolVal())
            ->check($data);
        // Password is optional if another domain than the local one is used
        V::attribute('password', V::stringType()->length(6, 255), $data->domain == User::DOMAIN_LOCAL)
            ->check($data);
        // Name duplication check
        if($this->getUserByName($data->name) != null) throw new BadRequestException(Users::ERROR_DUPLICATE);
        // Persistence
        $user = new User();
        $user->setName($data->name)
            ->setDomain($data->domain)
            ->setEmail($data->email)
            ->setRole($data->role)
            ->setNotifyOnSystemState($data->notifyOnSystemState)
            ->setRequirePasswordChange($data->requirePasswordChange);
        if(V::attribute('password')->validate($data))
            $user->setPassword($data->password);
        if(V::attribute('fullName')->validate($data)) $user->setFullName($data->fullName);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
        $this->log(sprintf('User %s (ID %d) created', $user->getName(), $user->getId()), LogEntry::RESOURCE_USERS, $user->getId());
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
     * @param stdClass $data
     * @return User
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('name', V::alnum()->length(1, 255))
            ->attribute('domain', V::intVal()->between(0, 1))
            ->attribute('fullName', V::stringType()->length(1, 255), false)
            ->attribute('email', Utils::emailValidator())
            ->attribute('role', V::intVal()->between(1, 3))
            ->attribute('notifyOnSystemState', V::boolVal())
            ->attribute('requirePasswordChange', V::boolVal())
            ->check($data);
        $user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->find($id);
        V::objectType()->check($user);
        // Only require a password if the user didn't have one previously (e.g. due to it being created as an LDAP account)
        $requirePassword = $data->domain == User::DOMAIN_LOCAL && $user->getPassword() == null && $user->getLegacyPassword() == null;
        V::attribute('password', V::stringType()->length(6, 255), $requirePassword)
            ->check($data);
        // Name duplication check
        $duplicate = $this->getUserByName($data->name);
        if($duplicate != null && $duplicate->getId() != $user->getId())
            throw new BadRequestException(Users::ERROR_DUPLICATE);
        // Persistence
        $user->setName($data->name)
            ->setDomain($data->domain)
            ->setEmail($data->email)
            ->setNotifyOnSystemState($data->notifyOnSystemState)
            ->setRequirePasswordChange($data->requirePasswordChange);
        // Set role, but force the first user to be an admin
        if($user->getId() != 1) $user->setRole($data->role);
        // Set optional password
        if(V::attribute('password')->validate($data)) {
            $user->setPassword($data->password)
                ->setLegacyPassword(null);
        };
        // Set optional full name
        if(V::attribute('fullName')->validate($data)) $user->setFullName($data->fullName);
        else $user->setFullName(null);
        $this->getEntityManager()->flush();
        $this->log(sprintf('User %s (ID %d) updated', $user->getName(), $user->getId()), LogEntry::RESOURCE_USERS, $user->getId());
        return $user;
    }

    /**
     * Updates the user the current session belongs to, e.g. allows logged-in users to change their own password.
     *
     * @param stdClass $data
     * @return User
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function updateSelf($data) {
        $this->assureAllowed('updateSelf');
        $em = $this->getEntityManager();
        $sessionUser = $em->getRepository('HoneySens\app\models\entities\User')->find($_SESSION['user']['id']);
        if($sessionUser == null || !$sessionUser->getRequirePasswordChange()) throw new ForbiddenException();
        // Validation
        V::objectType()->attribute('password', V::stringType()->length(6, 255))->check($data);
        if($sessionUser->getPassword() != null && password_verify($data->password, $sessionUser->getPassword())) throw new BadRequestException(Users::ERROR_REQUIRE_PASSWORD_CHANGE);
        // Persistence
        $sessionUser->setPassword($data->password)
            ->setLegacyPassword(null)
            ->setRequirePasswordChange(false);
        $em->flush();
        $this->log(sprintf('Password of user %s (ID %d) updated', $sessionUser->getName(), $sessionUser->getId()), LogEntry::RESOURCE_USERS, $sessionUser->getId());
        return $sessionUser;
    }

    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation: never remove the user with ID 1, who is always an admin
        V::intVal()->not(V::equals(1))->check($id);
        // Persistence
        $em = $this->getEntityManager();
        $user = $em->getRepository('HoneySens\app\models\entities\User')->find($id);
        V::objectType()->check($user);
        $uid = $user->getId();
        $em->remove($user);
        $em->flush();
        $this->log(sprintf('User %s (ID %d) deleted', $user->getName(), $uid), LogEntry::RESOURCE_USERS, $uid);
    }

    private function getUserByName($name) {
        return $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $name));
    }
}
