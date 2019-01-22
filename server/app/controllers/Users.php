<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\NotFoundException;
use Respect\Validation\Validator as V;

class Users extends RESTResource {

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
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $users = array();
            foreach($qb->getQuery()->getResult() as $user) {
                $users[] = $user->getState();
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
     *
     * @param stdClass $data
     * @return User
     */
	public function create($data) {
		$this->assureAllowed('create');
        // Validation
        V::objectType()
            ->attribute('name', V::alnum()->length(1, 255))
            ->attribute('email', V::email())
            ->attribute('password', V::stringType()->length(6, 255))
            ->attribute('role', V::intVal()->between(1, 3))
            ->check($data);
        // Persistence
		$user = new User();
		$user->setName($data->name)
			->setEmail($data->email)
			->setPassword($data->password)
			->setRole($data->role);
		$em = $this->getEntityManager();
		$em->persist($user);
		$em->flush();
		return $user;
	}

    /**
     * Updates an existing User object.
     * The following parameters are required:
     * - name: Login name of the user
     * - email: E-Mail address
     * - password: User password
     * - role: User role, determining his permissions (0 to 3)
     *
     * @param int $id
     * @param stdClass $data
     * @return User
     */
	public function update($id, $data) {
		$this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('name', V::alnum()->length(1, 255))
            ->attribute('email', V::email())
            ->attribute('role', V::intVal()->between(0, 3))
            ->check($data);
        // Persistence
		$user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->find($id);
        V::objectType()->check($user);
        $user->setName($data->name);
        $user->setEmail($data->email);
        // The user with ID 1 is always an admin
        if($user->getId() != 1) $user->setRole($data->role);
        if(V::attribute('password', V::stringType()->length(1, 255))->validate($data)) {
            $user->setPassword($data->password);
        }
		$this->getEntityManager()->flush();
        return $user;
	}
	
	public function delete($id) {
		$this->assureAllowed('delete');
        // Validation: never remove the user with ID 1, who is always an admin
        V::intVal()->not(V::equals(1))->check($id);
        // Persistence
		$em = $this->getEntityManager();
		$user = $em->getRepository('HoneySens\app\models\entities\User')->find($id);
        V::objectType()->check($user);
        $em->remove($user);
        $em->flush();
	}
}