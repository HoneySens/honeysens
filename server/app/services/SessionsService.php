<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\SystemException;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class SessionsService extends Service {

    const SESSION_TIMEOUT_DEFAULT = 1200;  # Seconds of inactivity until a regular session expires
    const SESSION_TIMEOUT_CHANGEPW = 600;  # Seconds until the "change password on first login" session expires

    private ConfigParser $config;
    private LogService $logger;
    private SystemService $systemService;
    private UsersService $usersService;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger, SystemService $systemService, UsersService $usersService) {
        parent::__construct($em);
        $this->config = $config;
        $this->logger = $logger;
        $this->systemService = $systemService;
        $this->usersService = $usersService;
    }

    /**
     * Authenticates a user with a given username and password.
     * Creates a new session on the server, adds session headers to the client response
     * and returns * an array carrying user data and associated permissions.
     *
     * @param string $username Name of the user to authenticate as
     * @param string $password Password to authenticate with
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function create(string $username, string $password): array {
        // Disable login if the installer hasn't run yet
        if($this->systemService->installRequired()) throw new ForbiddenException();
        try {
            $user = $this->em->getRepository('HoneySens\app\models\entities\User')->findOneBy(array('name' => $username));
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($user === null) throw new ForbiddenException();
        // The validation procedure heavily depends on the user's domain
        switch($user->getDomain()) {
            case User::DOMAIN_LOCAL:
                // Update password in case this user still relies on the deprecated hashing scheme
                if($user->getLegacyPassword() != null) {
                    if($user->getLegacyPassword() == sha1($password)) {
                        // Password match - update scheme
                        $user->setPassword($password);
                        $user->setLegacyPassword(null);
                        try {
                            $this->em->flush();
                        } catch(ORMException $e) {
                            throw new SystemException($e);
                        }
                    } else throw new ForbiddenException();
                }
                // Check password
                if($user->getPassword() != null && password_verify($password, $user->getPassword())) {
                    if($user->getRequirePasswordChange()) {
                        // User is not permitted to do anything except change his/her password
                        $guestUser = new User();
                        $guestUser->setRole(USER::ROLE_GUEST); // Temporarily assign guest permissions within this ession
                        $userState = $this->usersService->getStateWithPermissionConfig($user);
                        $userState['permissions'] = $guestUser->getState()['permissions'];
                        $userState['permissions']['users'] = array('updateSelf');
                        $sessionTimeout = self::SESSION_TIMEOUT_CHANGEPW;
                        $this->logger->log(sprintf('Password change request sent to user %s (ID %d)', $user->getName(), $user->getId()), LogResource::SESSIONS, null, $user->getId());
                    } else {
                        $userState = $this->usersService->getStateWithPermissionConfig($user);
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
                            if(ldap_bind($ldapHandle, str_replace('%s', $username, $this->config['ldap']['template']), $password))  {
                                $userState = $this->usersService->getStateWithPermissionConfig($user);
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
     * Close the server's active session of the currently logged in user.
     * Returns an empty guest user model to clear frontend caches.
     *
     * @param User $user Just used to log the identity of the user that's in process of being logged out.
     * @throws SystemException
     */
    public function delete(User $user): User {
        $guestUser = new User();
        $guestUser->setRole(User::ROLE_GUEST);
        $this->logger->log(sprintf('Logout by user %s (ID %d)', $user->getName(), $user->getId()), LogResource::SESSIONS, null, $user->getId());
        session_destroy();
        return $guestUser;
    }

}