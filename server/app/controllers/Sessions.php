<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use \Slim\Routing\RouteCollectorProxy;

class Sessions extends RESTResource {

    const SESSION_TIMEOUT_DEFAULT = 1200;  # Seconds of inactivity until a regular session expires
    const SESSION_TIMEOUT_CHANGEPW = 600;  # Seconds until the "change password on first login" session expires

    static function registerRoutes($sessions, $em, $services, $config) {
        $sessions->post('', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new Sessions($em, $services, $config);
            $userState = $controller->create($request->getParsedBody());
            $response->getBody()->write(json_encode($userState));
            return $response;
        });

        $sessions->delete('', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new Sessions($em, $services, $config);
            $user = $controller->destroy();
            $response->getBody()->write(json_encode($user->getState()));
            return $response;
        });
    }

    /**
     * Authenticates a user.
     *
     * @param array $data
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
        V::arrayType()
            ->key('username', V::stringType())
            ->key('password', V::stringType())
            ->check($data);
        $em = $this->getEntityManager();
        $user = $em->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $data['username']));
        if(!V::objectType()->validate($user)) throw new ForbiddenException();
        // The validation procedure heavily depends on the user's domain
        switch($user->getDomain()) {
            case User::DOMAIN_LOCAL:
                // Update password in case this user still relies on the deprecated hashing scheme
                if($user->getLegacyPassword() != null) {
                    if($user->getLegacyPassword() == sha1($data['password'])) {
                        // Password match - update scheme
                        $user->setPassword($data['password']);
                        $user->setLegacyPassword(null);
                        $em->flush();
                    } else throw new ForbiddenException();
                }
                // Check password
                if($user->getPassword() != null && password_verify($data['password'], $user->getPassword())) {
                    if($user->getRequirePasswordChange()) {
                        // User is not permitted to do anything except change his/her password
                        $guestUser = new User();
                        $guestUser->setRole(USER::ROLE_GUEST); // Temporarily assign guest permissions within this ession
                        $userState = $userController->getStateWithPermissionConfig($user);
                        $userState['permissions'] = $guestUser->getState()['permissions'];
                        $userState['permissions']['users'] = array('updateSelf');
                        $sessionTimeout = Sessions::SESSION_TIMEOUT_CHANGEPW;
                        $this->log(sprintf('Password change request sent to user %s (ID %d)', $user->getName(), $user->getId()), LogEntry::RESOURCE_SESSIONS, null, $user->getId());
                    } else {
                        $userState = $userController->getStateWithPermissionConfig($user);
                        $sessionTimeout = Sessions::SESSION_TIMEOUT_DEFAULT;
                        $this->log(sprintf('Successful login by user %s (ID %d)', $user->getName(), $user->getId()), LogEntry::RESOURCE_SESSIONS, null, $user->getId());
                    }
                    session_regenerate_id(true);
                    $_SESSION['user'] = $userState;
                    $_SESSION['authenticated'] = true;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['timeout'] = $sessionTimeout;
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
                            if(ldap_bind($ldapHandle, str_replace('%s', $data['username'], $config['ldap']['template']), $data['password']))  {
                                $userState = $userController->getStateWithPermissionConfig($user);
                                session_regenerate_id(true);
                                $_SESSION['user'] = $userState;
                                $_SESSION['authenticated'] = true;
                                $_SESSION['last_activity'] = time();
                                $this->log(sprintf('Successful login by user %s (ID %d)', $user->getName(), $user->getId()), LogEntry::RESOURCE_SESSIONS, null, $user->getId());
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
        $user = $this->getSessionUser();
        if($user != null) $this->log(sprintf('Logout by user %s (ID %d)', $user->getName(), $user->getId()), LogEntry::RESOURCE_SESSIONS, null, $user->getId());
        session_destroy();
        return $guestUser;
    }
}
