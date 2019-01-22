<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\ForbiddenException;
use Respect\Validation\Validator as V;

class Sessions extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->post('/api/sessions', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Sessions($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $authData = json_decode($request);
            $user = $controller->create($authData);
            echo json_encode($user->getState());
        });

        $app->delete('/api/sessions', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Sessions($em, $services, $config);
            $user = $controller->destroy();
            echo json_encode($user->getState());
        });
    }

    /**
     * Authenticates a user.
     *
     * @param stdClass $data
     * @return User
     * @throws ForbiddenException
     */
	public function create($data) {
        $config = $this->getConfig();
        // Disable login if the installer hasn't run yet
        if(System::installRequired($config)) {
            throw new ForbiddenException();
        }
        // Validation
        V::objectType()
            ->attribute('username', V::stringType())
            ->attribute('password', V::stringType())
            ->check($data);
        $user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $data->username));
        if(!V::objectType()->validate($user)) {
            throw new ForbiddenException();
        }
        // Check parameters and perform login
        // TODO Add salt
        if($user->getPassword() == $data->password) {
            $_SESSION['user'] = $user->getState();
            $_SESSION['authenticated'] = true;
            return $user;
        } else {
            throw new ForbiddenException();
        }
	}

    /**
     * Destroy the session of the current user.
     *
     * @return User
     */
	public function destroy() {
		$guestUser = new User();
		$guestUser->setRole(User::ROLE_GUEST);
        session_destroy();
		return $guestUser;
	}
}