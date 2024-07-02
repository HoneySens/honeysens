<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Respect\Validation\Validator as V;

class SessionsService extends Service {

    const SESSION_TIMEOUT_DEFAULT = 1200;  # Seconds of inactivity until a regular session expires
    const SESSION_TIMEOUT_CHANGEPW = 600;  # Seconds until the "change password on first login" session expires

    private ConfigParser $config;
    private LogService $logger;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger) {
        parent::__construct($em);
        $this->config = $config;
        $this->logger = $logger;
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
    public function create($data, UsersService $usersService, SystemService $systemService) {
        // Disable login if the installer hasn't run yet
        if($systemService->installRequired()) throw new ForbiddenException();
        // Validation
        V::arrayType()
            ->key('username', V::stringType())
            ->key('password', V::stringType())
            ->check($data);
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $data['username']));
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
                        $this->em->flush();
                    } else throw new ForbiddenException();
                }
                // Check password
                if($user->getPassword() != null && password_verify($data['password'], $user->getPassword())) {
                    if($user->getRequirePasswordChange()) {
                        // User is not permitted to do anything except change his/her password
                        $guestUser = new User();
                        $guestUser->setRole(USER::ROLE_GUEST); // Temporarily assign guest permissions within this ession
                        $userState = $usersService->getStateWithPermissionConfig($user);
                        $userState['permissions'] = $guestUser->getState()['permissions'];
                        $userState['permissions']['users'] = array('updateSelf');
                        $sessionTimeout = self::SESSION_TIMEOUT_CHANGEPW;
                        $this->logger->log(sprintf('Password change request sent to user %s (ID %d)', $user->getName(), $user->getId()), LogResource::SESSIONS, null, $user->getId());
                    } else {
                        $userState = $usersService->getStateWithPermissionConfig($user);
                        $sessionTimeout = self::SESSION_TIMEOUT_DEFAULT;
                        $this->logger->log(sprintf('Successful login by user %s (ID %d)', $user->getName(), $user->getId()), LogResource::SESSIONS, null, $user->getId());
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
                if($this->config['ldap']['enabled'] == 'true') {
                    $ldapSchema = $this->config['ldap']['encryption'] == '2' ? 'ldaps' : 'ldap';
                    if($ldapHandle = ldap_connect(sprintf('%s://%s:%s', $ldapSchema, $this->config['ldap']['server'], $this->config['ldap']['port']))) {
                        ldap_set_option($ldapHandle, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($ldapHandle, LDAP_OPT_REFERRALS, 0);
                        try {
                            if($this->config['ldap']['encryption'] == '1') ldap_start_tls($ldapHandle);
                            if(ldap_bind($ldapHandle, str_replace('%s', $data['username'], $this->config['ldap']['template']), $data['password']))  {
                                $userState = $usersService->getStateWithPermissionConfig($user);
                                session_regenerate_id(true);
                                $_SESSION['user'] = $userState;
                                $_SESSION['authenticated'] = true;
                                $_SESSION['last_activity'] = time();
                                $this->logger->log(sprintf('Successful login by user %s (ID %d)', $user->getName(), $user->getId()), LogResource::SESSIONS, null, $user->getId());
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
     * Delete the session of a user.
     *
     * @return User
     */
    public function delete(User $user) {
        $guestUser = new User();
        $guestUser->setRole(User::ROLE_GUEST);
        if($user != null) $this->logger->log(sprintf('Logout by user %s (ID %d)', $user->getName(), $user->getId()), LogResource::SESSIONS, null, $user->getId());
        session_destroy();
        return $guestUser;
    }

}