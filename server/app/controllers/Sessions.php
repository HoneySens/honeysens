<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
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
            echo json_encode($user);
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
     * @return array
     * @throws ForbiddenException
     * @throws BadRequestException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create($data) {
        $config = $this->getConfig();
        $userController = new Users($this->getEntityManager(), $this->getServiceManager(), $config);
        // Disable login if the installer hasn't run yet
        if(System::installRequired($config)) {
            throw new ForbiddenException();
        }
        // Validation
        V::objectType()
            ->attribute('username', V::stringType())
            ->attribute('password', V::stringType())
            ->check($data);
        $em = $this->getEntityManager();
        $user = $em->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $data->username));
        if(!V::objectType()->validate($user)) throw new ForbiddenException();
        // The validation procedure heavily depends on the user's domain
        switch($user->getDomain()) {
            case User::DOMAIN_LOCAL:
                // Update password in case this user still relies on the deprecated hashing scheme
                if($user->getLegacyPassword() != null) {
                    if($user->getLegacyPassword() == sha1($data->password)) {
                        // Password match - update scheme
                        $user->setPassword($data->password);
                        $user->setLegacyPassword(null);
                        $em->flush();
                    } else throw new ForbiddenException();
                }
                // Check password
                if($user->getPassword() != null && password_verify($data->password, $user->getPassword())) {
                    $userState = $userController->getStateWithPermissionConfig($user);
                    $_SESSION['user'] = $userState;
                    $_SESSION['authenticated'] = true;
                    return $userState;
                } else throw new ForbiddenException();
                break;
            case User::DOMAIN_LDAP:
                if($config['ldap']['enabled'] == 'true') {
                    $ldapSchema = $config['ldap']['encryption'] == '2' ? 'ldaps' : 'ldap';
                    if($ldapHandle = ldap_connect(sprintf('%s://%s:%s', $ldapSchema, $config['ldap']['server'], $config['ldap']['port']))) {
                        ldap_set_option($ldapHandle, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($ldapHandle, LDAP_OPT_REFERRALS, 0);
                        try {
                            if($config['ldap']['encryption'] == '1') ldap_start_tls($ldapHandle);
                            if(ldap_bind($ldapHandle, str_replace('%s', $data->username, $config['ldap']['template']), $data->password))  {
                                $userState = $userController->getStateWithPermissionConfig($user);
                                $_SESSION['user'] = $userState;
                                $_SESSION['authenticated'] = true;
                                return $userState;
                            } else throw new ForbiddenException();
                        } catch(\Exception $e) {
                            throw new ForbiddenException();
                        }
                    }
                }
                break;
        }
        // Fall-through exception
        throw new ForbiddenException();
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