<?php
namespace HoneySens\app\services;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use HoneySens\app\adapters\EMailAdapter;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\TransportEncryptionType;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\SystemException;
use HoneySens\app\services\dto\SettingsParams;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class SettingsService extends Service {

    private ConfigParser $config;
    private EMailAdapter $emailAdapter;
    private LogService $logger;
    private TaskAdapter $taskAdapter;

    public function __construct(ConfigParser $config, EntityManager $em, EMailAdapter $emailAdapter, LogService $logger, TaskAdapter $taskAdapter) {
        parent::__construct($em);
        $this->config = $config;
        $this->emailAdapter = $emailAdapter;
        $this->logger = $logger;
        $this->taskAdapter = $taskAdapter;
    }

    /**
     * Returns the current system-wide application settings.
     *
     * @param bool $includeAdminSettings Also include sensitive data only relevant for admins
     * @todo This silently returns nothing if the config is invalid
     */
    public function get(bool $includeAdminSettings = false): array {
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
        if($includeAdminSettings) {
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
     *
     * @param SettingsParams $params Updated system settings
     * @throws SystemException
     */
    public function update(SettingsParams $params): array {
        $this->config->set('server', 'host', $params->serverHost);
        $this->config->set('server', 'portHTTPS', $params->serverPortHTTPS);
        $this->config->set('smtp', 'enabled', $params->smtpEnabled ? 'true' : 'false');
        $this->config->set('smtp', 'server', $params->smtpServer);
        $this->config->set('smtp', 'port', $params->smtpPort);
        $this->config->set('smtp', 'encryption', $params->smtpEncryption?->value);
        $this->config->set('smtp', 'from', $params->smtpFrom);
        $this->config->set('smtp', 'user', $params->smtpUser);
        try {
            $this->config->set('smtp', 'password', $params->smtpPassword);
        } catch(\Error) {
            // Only update the password if a new one was given. Otherwise, keep the existing one.
        }
        $this->config->set('ldap', 'enabled', $params->ldapEnabled ? 'true' : 'false');
        $this->config->set('ldap', 'server', $params->ldapServer);
        $this->config->set('ldap', 'port', $params->ldapPort);
        $this->config->set('ldap', 'encryption', $params->ldapEncryption?->value);
        $this->config->set('ldap', 'template', $params->ldapTemplate);
        $this->config->set('syslog', 'enabled', $params->syslogEnabled ? 'true' : 'false');
        $this->config->set('syslog', 'server', $params->syslogServer);
        $this->config->set('syslog', 'port', $params->syslogPort);
        $this->config->set('syslog', 'transport', $params->syslogTransport?->value);
        $this->config->set('syslog', 'facility', $params->syslogFacility);
        $this->config->set('syslog', 'priority', $params->syslogPriority);
        $this->config->set('sensors', 'update_interval', $params->sensorsUpdateInterval);
        $this->config->set('sensors', 'service_network', $params->sensorsServiceNetwork);
        $this->config->set('sensors', 'timeout_threshold', $params->sensorsTimeoutThreshold);
        $this->config->set('misc', 'api_log_keep_days', $params->apiLogKeepDays);
        $this->config->set('misc', 'prevent_event_deletion_by_managers', $params->preventEventDeletionByManagers ? 'true' : 'false');
        $this->config->set('misc', 'prevent_sensor_deletion_by_managers', $params->preventSensorDeletionByManagers ? 'true' : 'false');
        $this->config->set('misc', 'require_event_comment', $params->requireEventComment ? 'true' : 'false');
        $this->config->set('misc', 'require_filter_description', $params->requireFilterDescription ? 'true' : 'false');
        $this->config->set('misc', 'archive_prefer', $params->archivePrefer ? 'true' : 'false');
        $this->config->set('misc', 'archive_move_days', $params->archiveMoveDays);
        $this->config->set('misc', 'archive_keep_days', $params->archiveKeepDays);
        $this->config->save();
        try {
            $this->em->getConnection()->executeStatement('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "settings"');
        } catch(Exception $e) {
            throw new SystemException($e);
        }
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
     * Creates and enqueues a task that sends a test e-mail.
     * Returns the task instance to enable progress tracking on the client side.
     *
     * @param string $from Sender e-mail address
     * @param string $to Recipient e-mail address
     * @param string $smtpServer Host name or IP address of the SMTP server to connect to
     * @param int $smtpPort TCP port of the SMTP server to connect to
     * @param TransportEncryptionType $smtpEncryption TCP transport encryption mode
     * @param string $smtpUser User to authenticate as via SMTP
     * @param string $smtpPassword Password to authenticate with via SMTP
     * @param User $sessionUser User to enqueue the task as
     * @throws SystemException
     */
    public function sendTestMail(string $from, string $to, string $smtpServer, int $smtpPort, TransportEncryptionType $smtpEncryption, string $smtpUser, string $smtpPassword, User $sessionUser): Task {
        $this->logger->log(sprintf('Test E-Mail sent to %s', $to), LogResource::SETTINGS);
        return $this->emailAdapter->sendTestMail($sessionUser, $from, $to, $smtpServer, $smtpPort, $smtpEncryption, $smtpUser, $smtpPassword);
    }

    /**
     * Generates a syslog test events and sends it to the configured syslog server.
     * Returns the generated raw event data.
     *
     * @return array
     * @throws SystemException
     */
    public function sendTestEvent(): array {
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
