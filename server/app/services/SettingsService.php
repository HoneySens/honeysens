<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\adapters\EMailAdapter;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\Utils;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Respect\Validation\Validator as V;

class SettingsService {

    private ConfigParser $config;
    private EMailAdapter $emailAdapter;
    private EntityManager $em;
    private LogService $logger;
    private TaskAdapter $taskAdapter;

    public function __construct(ConfigParser $config, EntityManager $em, EMailAdapter $emailAdapter, LogService $logger, TaskAdapter $taskAdapter) {
        $this->config = $config;
        $this->em= $em;
        $this->emailAdapter = $emailAdapter;
        $this->logger = $logger;
        $this->taskAdapter = $taskAdapter;
    }

    /**
     * Returns the current system-wide settings.
     *
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get($userID) {
        // TODO This silently returns nothing if the config is invalid
        $caCert = file_get_contents(APPLICATION_PATH . '/../data/CA/ca.crt');
        $settings = array(
            'id' => 0,
            'serverHost' => $this->config['server']['host'],
            'serverPortHTTPS' => $this->config['server']['portHTTPS'],
            'sensorsUpdateInterval' => $this->config['sensors']['update_interval'],
            'sensorsServiceNetwork' => $this->config['sensors']['service_network'],
            'sensorsTimeoutThreshold' => $this->config['sensors']['timeout_threshold'],
            'caFP' => openssl_x509_fingerprint($caCert),
            'caExpire' => openssl_x509_parse($caCert)['validTo_time_t'],
            'requireEventComment' => $this->config->getBoolean('misc', 'require_event_comment'),
            'requireFilterDescription' => $this->config->getBoolean('misc', 'require_filter_description'),
            'archivePrefer' => $this->config->getBoolean('misc', 'archive_prefer'),
            'preventEventDeletionByManagers' => $this->config->getBoolean('misc', 'prevent_event_deletion_by_managers'),
            'preventSensorDeletionByManagers' => $this->config->getBoolean('misc', 'prevent_sensor_deletion_by_managers')
        );
        // Settings only relevant to admins
        if($userID == null) {
            // SMTP
            $settings['smtpEnabled'] = $this->config->getBoolean('smtp', 'enabled');
            $settings['smtpServer'] = $this->config['smtp']['server'];
            $settings['smtpPort'] = $this->config['smtp']['port'];
            $settings['smtpEncryption'] = $this->config['smtp']['encryption'];
            $settings['smtpFrom'] = $this->config['smtp']['from'];
            $settings['smtpUser'] = $this->config['smtp']['user'];
            $settings['smtpPassword'] = $this->config['smtp']['password'];
            // LDAP
            $settings['ldapEnabled'] = $this->config->getBoolean('ldap', 'enabled');
            $settings['ldapServer'] = $this->config['ldap']['server'];
            $settings['ldapPort'] = $this->config['ldap']['port'];
            $settings['ldapEncryption'] = $this->config['ldap']['encryption'];
            $settings['ldapTemplate'] = $this->config['ldap']['template'];
            // Event Forwarding (syslog)
            $settings['syslogEnabled'] = $this->config->getBoolean('syslog', 'enabled');
            $settings['syslogServer'] = $this->config['syslog']['server'];
            $settings['syslogPort'] = $this->config['syslog']['port'];
            $settings['syslogTransport'] = $this->config['syslog']['transport'];
            $settings['syslogFacility'] = $this->config['syslog']['facility'];
            $settings['syslogPriority'] = $this->config['syslog']['priority'];
            // Misc
            $settings['apiLogKeepDays'] = $this->config['misc']['api_log_keep_days'];
            $settings['archiveMoveDays'] = $this->config['misc']['archive_move_days'];
            $settings['archiveKeepDays'] = $this->config['misc']['archive_keep_days'];
        }
        return $settings;
    }

    /**
     * Updates the system-wide settings.
     * The following parameters are required:
     * - serverHost: The hostname the server is reachable as
     * - serverPortHTTPS: TCP port the server offers its API
     * - smtpEnabled: SMTP mail notification status
     * - ldapEnabled: LDAP status
     * - syslogEnabled: Event forwarding (syslog) status
     * - sensorsUpdateInterval: The delay between status update connection attempts initiated by sensors
     * - sensorsServiceNetwork: The internal network range that sensors should use for service containers
     * - sensorsTimeoutThreshold: Period (in minutes) that needs to pass after the last contact until a sensor is declared as 'offline'
     * - apiLogKeepDays: Specifies how many days the API log should be kept (if API log is enabled)
     * - preventEventDeletionByManagers: If true, manager can move events to the archive, but not delete them
     * - preventSensorDeletionByManagers: If true, managers are not permitted to delete sensors
     * - requireEventComment: Forces users to enter a comment when editing events
     * - requireFilterDescription: Forces users to enter a description when creating or updating event filters
     * - archivePrefer: Instructs the client to preselect the "archive" checkbox by default when deleting events
     * - archiveMoveDays: Specifies after how many days after their last modification events are moved into the archive
     * - archiveKeepDays: Specifies how many days archived events should be kept
     *
     * Optional parameters:
     * - smtpServer: IP or hostname of a mail server
     * - smtpPort: TCP port to use for SMTP connections
     * - smtpEncryption: SMTP transport encryption (0: none, 1: STARTTLS, 2: TLS)
     * - smtpFrom: E-Mail address to use as sender of system mails
     * - smtpUser: SMTP Username to authenticate with
     * - smtpPassword: SMTP Password to authenticate with
     * - ldapServer: IP or hostname of an LDAP server
     * - ldapPort: TCP port to use for LDAP connections
     * - ldapEncryption: LDAP transport encryption (0: none, 1: STARTTLS, 2: TLS)
     * - ldapTemplate: LDAP template string
     * - syslogServer: IP or hostname of a syslog server
     * - syslogPort: Port to use for syslog connections
     * - syslogTransport: Transport protocol to use for syslog connection (0: UDP, 1: TCP)
     * - syslogFacility: Facility according to syslog protocol (between 0 and 23)
     * - syslogPriority: Priority according to syslog protocol (2, 3, 4, 6, 7)
     *
     * @param array $data
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update($data) {
        // Validation
        V::arrayType()
            ->key('serverHost', V::stringType())
            ->key('serverPortHTTPS', V::intVal()->between(0, 65535))
            ->key('smtpEnabled', V::boolType())
            ->key('ldapEnabled', V::boolType())
            ->key('syslogEnabled', V::boolType())
            ->key('sensorsUpdateInterval', V::intVal()->between(1, 60))
            ->key('sensorsServiceNetwork', V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/'))
            ->key('sensorsTimeoutThreshold', V::intVal()->between(1, 1440))
            ->key('apiLogKeepDays', V::intVal()->between(0, 65535))
            ->key('preventEventDeletionByManagers', V::boolType())
            ->key('preventSensorDeletionByManagers', V::boolType())
            ->key('requireEventComment', V::boolType())
            ->key('requireFilterDescription', V::boolType())
            ->key('archivePrefer', V::boolType())
            ->key('archiveMoveDays', V::intVal()->between(0, 65535))
            ->key('archiveKeepDays', V::intVal()->between(0, 65535))
            ->check($data);
        if($data['smtpEnabled']) {
            V::key('smtpServer', V::stringType())
                ->key('smtpPort', V::intVal()->between(0, 65535))
                ->key('smtpEncryption', V::intVal()->between(0, 2))
                ->key('smtpFrom', Utils::emailValidator())
                ->key('smtpUser', V::stringType())
                ->key('smtpPassword', V::stringType())
                ->check($data);
        } else {
            V::key('smtpServer', V::optional(V::stringType()))
                ->key('smtpPort', V::optional(V::intVal()->between(0, 65535)))
                ->key('smtpEncryption', V::optional(V::intVal()->between(0, 2)))
                ->key('smtpFrom', V::optional(Utils::emailValidator()))
                ->key('smtpUser', V::optional(V::stringType()))
                ->key('smtpPassword', V::optional(V::stringType()))
                ->check($data);
        }
        if($data['ldapEnabled']) {
            V::key('ldapServer', V::stringType())
                ->key('ldapPort', V::intVal()->between(0, 65535))
                ->key('ldapEncryption', V::intVal()->between(0, 2))
                ->key('ldapTemplate', V::stringType())
                ->check($data);
        } else {
            V::key('ldapServer', V::optional(V::stringType()))
                ->key('ldapPort', V::optional(V::intVal()->between(0, 65535)))
                ->key('ldapEncryption', V::optional(V::intVal()->between(0, 2)))
                ->key('ldapTemplate', V::optional(V::stringType()))
                ->check($data);
        }
        if($data['syslogEnabled']) {
            V::key('syslogServer', V::stringType())
                ->key('syslogPort', V::intVal()->between(0, 65535))
                ->key('syslogTransport', V::intVal()->between(0, 1))
                ->key('syslogFacility', V::oneOf(V::intVal()->between(0, 11), V::intVal()->between(16, 23)))
                ->key('syslogPriority', V::oneOf(V::intVal()->between(2, 4), V::intVal()->between(6, 7)))
                ->check($data);
        } else {
            V::key('syslogServer', V::optional(V::stringType()))
                ->key('syslogPort', V::optional(V::intVal()->between(0, 65535)))
                ->key('syslogTransport', V::optional(V::intVal()->between(0, 1)))
                ->key('syslogFacility', V::optional(V::oneOf(V::intVal()->between(0, 11), V::intVal()->between(16, 23))))
                ->key('syslogPriority', V::optional(V::oneOf(V::intVal()->between(2, 4), V::intVal()->between(6, 7))))
                ->check($data);
        }
        // Persistence
        $this->config->set('server', 'host', $data['serverHost']);
        $this->config->set('server', 'portHTTPS', $data['serverPortHTTPS']);
        $this->config->set('smtp', 'enabled', $data['smtpEnabled'] ? 'true' : 'false');
        $this->config->set('smtp', 'server', $data['smtpServer']);
        $this->config->set('smtp', 'port', $data['smtpPort']);
        $this->config->set('smtp', 'encryption', $data['smtpEncryption']);
        $this->config->set('smtp', 'from', $data['smtpFrom']);
        $this->config->set('smtp', 'user', $data['smtpUser']);
        $this->config->set('smtp', 'password', $data['smtpPassword']);
        $this->config->set('ldap', 'enabled', $data['ldapEnabled'] ? 'true' : 'false');
        $this->config->set('ldap', 'server', $data['ldapServer']);
        $this->config->set('ldap', 'port', $data['ldapPort']);
        $this->config->set('ldap', 'encryption', $data['ldapEncryption']);
        $this->config->set('ldap', 'template', $data['ldapTemplate']);
        $this->config->set('syslog', 'enabled', $data['syslogEnabled'] ? 'true' : 'false');
        $this->config->set('syslog', 'server', $data['syslogServer']);
        $this->config->set('syslog', 'port', $data['syslogPort']);
        $this->config->set('syslog', 'transport', $data['syslogTransport']);
        $this->config->set('syslog', 'facility', $data['syslogFacility']);
        $this->config->set('syslog', 'priority', $data['syslogPriority']);
        $this->config->set('sensors', 'update_interval', $data['sensorsUpdateInterval']);
        $this->config->set('sensors', 'service_network', $data['sensorsServiceNetwork']);
        $this->config->set('sensors', 'timeout_threshold', $data['sensorsTimeoutThreshold']);
        $this->config->set('misc', 'api_log_keep_days', $data['apiLogKeepDays']);
        $this->config->set('misc', 'prevent_event_deletion_by_managers', $data['preventEventDeletionByManagers'] ? 'true' : 'false');
        $this->config->set('misc', 'prevent_sensor_deletion_by_managers', $data['preventSensorDeletionByManagers'] ? 'true' : 'false');
        $this->config->set('misc', 'require_event_comment', $data['requireEventComment'] ? 'true' : 'false');
        $this->config->set('misc', 'require_filter_description', $data['requireFilterDescription'] ? 'true' : 'false');
        $this->config->set('misc', 'archive_prefer', $data['archivePrefer'] ? 'true' : 'false');
        $this->config->set('misc', 'archive_move_days', $data['archiveMoveDays']);
        $this->config->set('misc', 'archive_keep_days', $data['archiveKeepDays']);
        $this->config->save();
        $this->em->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "settings"');
        $this->logger->log('System settings updated', LogResource::SETTINGS);
        return array(
            'id' => 0,
            'serverHost' => $this->config['server']['host'],
            'serverPortHTTPS' => $this->config['server']['portHTTPS'],
            'smtpEnabled' => $this->config->getBoolean('smtp', 'enabled'),
            'smtpServer' => $this->config['smtp']['server'],
            'smtpPort' => $this->config['smtp']['port'],
            'smtpEncryption' => $this->config['smtp']['encryption'],
            'smtpFrom' => $this->config['smtp']['from'],
            'smtpUser' => $this->config['smtp']['user'],
            'smtpPassword' => $this->config['smtp']['password'],
            'ldapEnabled' => $this->config->getBoolean('ldap', 'enabled'),
            'ldapServer' => $this->config['ldap']['server'],
            'ldapPort' => $this->config['ldap']['port'],
            'ldapEncryption' => $this->config['ldap']['encryption'],
            'ldapTemplate' => $this->config['ldap']['template'],
            'syslogEnabled' => $this->config->getBoolean('syslog', 'enabled'),
            'syslogServer' => $this->config['syslog']['server'],
            'syslogPort' => $this->config['syslog']['port'],
            'syslogTransport' => $this->config['syslog']['transport'],
            'syslogFacility' => $this->config['syslog']['facility'],
            'syslogPriority' => $this->config['syslog']['priority'],
            'sensorsUpdateInterval' => $this->config['sensors']['update_interval'],
            'sensorsServiceNetwork' => $this->config['sensors']['service_network'],
            'sensorsTimeoutThreshold' => $this->config['sensors']['timeout_threshold'],
            'apiLogKeepDays' => $this->config['misc']['api_log_keep_days'],
            'preventEventDeletionByManagers' => $this->config->getBoolean('misc', 'prevent_event_deletion_by_managers'),
            'preventSensorDeletionByManagers' => $this->config->getBoolean('misc', 'prevent_sensor_deletion_by_managers'),
            'requireEventComment' => $this->config->getBoolean('misc', 'require_event_comment'),
            'requireFilterDescription' => $this->config->getBoolean('misc', 'require_filter_description'),
            'archivePrefer' => $this->config->getBoolean('misc', 'archive_prefer'),
            'archiveMoveDays' => $this->config['misc']['archive_move_days'],
            'archiveKeepDays' => $this->config['misc']['archive_keep_days']
        );
    }

    /**
     * Sends a test e-email via a given SMTP server.
     *
     * @param array $data
     * @return Task
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function sendTestMail($data, User $sessionUser) {
        // Validation
        V::arrayType()
            ->key('recipient', Utils::emailValidator())
            ->key('smtpServer', V::stringType())
            ->key('smtpPort', V::intVal()->between(0, 65535))
            ->key('smtpEncryption', V::intVal()->between(0, 2))
            ->key('smtpUser', V::stringType())
            ->key('smtpFrom', Utils::emailValidator())
            ->key('smtpPassword', V::stringType())
            ->check($data);
        // Send mail
        $this->logger->log(sprintf('Test E-Mail sent to %s', $data['recipient']), LogResource::SETTINGS);
        return $this->emailAdapter->sendTestMail($sessionUser, $data['smtpFrom'], $data['recipient'], $data['smtpServer'], $data['smtpPort'], $data['smtpEncryption'], $data['smtpUser'], $data['smtpPassword']);
    }

    public function sendTestEvent() {
        // Generate example event data
        $now = new \DateTime();
        $ev = array(
            'id' => mt_rand(1, 1000),
            'timestamp' => $now->format('U'),
            'sensor_id' => mt_rand(1, 10),
            'sensor_name' => 'sensor_' . mt_rand(100, 210),
            'service' => mt_rand(0, 2),
            'classification' => mt_rand(2, 4),
            'source' => "".mt_rand(0,255).".".mt_rand(0,255).".".mt_rand(0,255).".".mt_rand(0,255),
            'summary' => 'Test event from HoneySens',
            'status' => 0,
            'comment' => ''
        );
        $this->taskAdapter->enqueue(null, Task::TYPE_EVENT_FORWARDER, array('event' => $ev));
        $this->logger->log('Syslog test event forwarded', LogResource::SESSIONS);
        return $ev;
    }
}
