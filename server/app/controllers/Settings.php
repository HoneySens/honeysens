<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\ServiceManager;
use HoneySens\app\models\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Settings extends RESTResource {

    static function registerRoutes($app, $em, $services, $config) {
        $app->get('/api/settings', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Settings($em, $services, $config);
            $settings = $controller->get();
            $response->getBody()->write(json_encode($settings));
            return $response;
        });

        $app->put('/api/settings', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Settings($em, $services, $config);
            $settings = $controller->update($request->getParsedBody());
            $response->getBody()->write(json_encode($settings));
            return $response;
        });

        $app->post('/api/settings/testmail', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Settings($em, $services, $config);
            $task = $controller->sendTestMail($request->getParsedBody());
            $response->getBody()->write(json_encode($task->getState()));
            return $response;
        });

        $app->post('/api/settings/testevent', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Settings($em, $services, $config);
            $response->getBody()->write(json_encode($controller->sendTestEvent()));
            return $response;
        });
    }

    /**
     * Returns the current system-wide settings.
     *
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get() {
        $this->assureAllowed('get');
        // TODO This silently returns nothing if the config is invalid
        $config = $this->getConfig();
        $caCert = file_get_contents(APPLICATION_PATH . '/../data/CA/ca.crt');
        $settings = array(
            'id' => 0,
            'serverHost' => $config['server']['host'],
            'serverPortHTTPS' => $config['server']['portHTTPS'],
            'sensorsUpdateInterval' => $config['sensors']['update_interval'],
            'sensorsServiceNetwork' => $config['sensors']['service_network'],
            'sensorsTimeoutThreshold' => $config['sensors']['timeout_threshold'],
            'caFP' => openssl_x509_fingerprint($caCert),
            'caExpire' => openssl_x509_parse($caCert)['validTo_time_t'],
            'requireEventComment' => $config->getBoolean('misc', 'require_event_comment'),
            'requireFilterDescription' => $config->getBoolean('misc', 'require_filter_description'),
            'archivePrefer' => $config->getBoolean('misc', 'archive_prefer'),
            'preventEventDeletionByManagers' => $config->getBoolean('misc', 'prevent_event_deletion_by_managers'),
            'preventSensorDeletionByManagers' => $config->getBoolean('misc', 'prevent_sensor_deletion_by_managers')
        );
        // Settings only relevant to admins
        if($this->getSessionUserID() == null) {
            // SMTP
            $settings['smtpEnabled'] = $config->getBoolean('smtp', 'enabled');
            $settings['smtpServer'] = $config['smtp']['server'];
            $settings['smtpPort'] = $config['smtp']['port'];
            $settings['smtpEncryption'] = $config['smtp']['encryption'];
            $settings['smtpFrom'] = $config['smtp']['from'];
            $settings['smtpUser'] = $config['smtp']['user'];
            $settings['smtpPassword'] = $config['smtp']['password'];
            // LDAP
            $settings['ldapEnabled'] = $config->getBoolean('ldap', 'enabled');
            $settings['ldapServer'] = $config['ldap']['server'];
            $settings['ldapPort'] = $config['ldap']['port'];
            $settings['ldapEncryption'] = $config['ldap']['encryption'];
            $settings['ldapTemplate'] = $config['ldap']['template'];
            // Event Forwarding (syslog)
            $settings['syslogEnabled'] = $config->getBoolean('syslog', 'enabled');
            $settings['syslogServer'] = $config['syslog']['server'];
            $settings['syslogPort'] = $config['syslog']['port'];
            $settings['syslogTransport'] = $config['syslog']['transport'];
            $settings['syslogFacility'] = $config['syslog']['facility'];
            $settings['syslogPriority'] = $config['syslog']['priority'];
            // Misc
            $settings['apiLogKeepDays'] = $config['misc']['api_log_keep_days'];
            $settings['archiveMoveDays'] = $config['misc']['archive_move_days'];
            $settings['archiveKeepDays'] = $config['misc']['archive_keep_days'];
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
        $this->assureAllowed('update');
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
        $config = $this->getConfig();
        $config->set('server', 'host', $data['serverHost']);
        $config->set('server', 'portHTTPS', $data['serverPortHTTPS']);
        $config->set('smtp', 'enabled', $data['smtpEnabled'] ? 'true' : 'false');
        $config->set('smtp', 'server', $data['smtpServer']);
        $config->set('smtp', 'port', $data['smtpPort']);
        $config->set('smtp', 'encryption', $data['smtpEncryption']);
        $config->set('smtp', 'from', $data['smtpFrom']);
        $config->set('smtp', 'user', $data['smtpUser']);
        $config->set('smtp', 'password', $data['smtpPassword']);
        $config->set('ldap', 'enabled', $data['ldapEnabled'] ? 'true' : 'false');
        $config->set('ldap', 'server', $data['ldapServer']);
        $config->set('ldap', 'port', $data['ldapPort']);
        $config->set('ldap', 'encryption', $data['ldapEncryption']);
        $config->set('ldap', 'template', $data['ldapTemplate']);
        $config->set('syslog', 'enabled', $data['syslogEnabled'] ? 'true' : 'false');
        $config->set('syslog', 'server', $data['syslogServer']);
        $config->set('syslog', 'port', $data['syslogPort']);
        $config->set('syslog', 'transport', $data['syslogTransport']);
        $config->set('syslog', 'facility', $data['syslogFacility']);
        $config->set('syslog', 'priority', $data['syslogPriority']);
        $config->set('sensors', 'update_interval', $data['sensorsUpdateInterval']);
        $config->set('sensors', 'service_network', $data['sensorsServiceNetwork']);
        $config->set('sensors', 'timeout_threshold', $data['sensorsTimeoutThreshold']);
        $config->set('misc', 'api_log_keep_days', $data['apiLogKeepDays']);
        $config->set('misc', 'prevent_event_deletion_by_managers', $data['preventEventDeletionByManagers'] ? 'true' : 'false');
        $config->set('misc', 'prevent_sensor_deletion_by_managers', $data['preventSensorDeletionByManagers'] ? 'true' : 'false');
        $config->set('misc', 'require_event_comment', $data['requireEventComment'] ? 'true' : 'false');
        $config->set('misc', 'require_filter_description', $data['requireFilterDescription'] ? 'true' : 'false');
        $config->set('misc', 'archive_prefer', $data['archivePrefer'] ? 'true' : 'false');
        $config->set('misc', 'archive_move_days', $data['archiveMoveDays']);
        $config->set('misc', 'archive_keep_days', $data['archiveKeepDays']);
        $config->save();
        $this->getEntityManager()->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "settings"');
        $this->log('System settings updated', LogEntry::RESOURCE_SETTINGS);
        return array(
            'id' => 0,
            'serverHost' => $config['server']['host'],
            'serverPortHTTPS' => $config['server']['portHTTPS'],
            'smtpEnabled' => $config->getBoolean('smtp', 'enabled'),
            'smtpServer' => $config['smtp']['server'],
            'smtpPort' => $config['smtp']['port'],
            'smtpEncryption' => $config['smtp']['encryption'],
            'smtpFrom' => $config['smtp']['from'],
            'smtpUser' => $config['smtp']['user'],
            'smtpPassword' => $config['smtp']['password'],
            'ldapEnabled' => $config->getBoolean('ldap', 'enabled'),
            'ldapServer' => $config['ldap']['server'],
            'ldapPort' => $config['ldap']['port'],
            'ldapEncryption' => $config['ldap']['encryption'],
            'ldapTemplate' => $config['ldap']['template'],
            'syslogEnabled' => $config->getBoolean('syslog', 'enabled'),
            'syslogServer' => $config['syslog']['server'],
            'syslogPort' => $config['syslog']['port'],
            'syslogTransport' => $config['syslog']['transport'],
            'syslogFacility' => $config['syslog']['facility'],
            'syslogPriority' => $config['syslog']['priority'],
            'sensorsUpdateInterval' => $config['sensors']['update_interval'],
            'sensorsServiceNetwork' => $config['sensors']['service_network'],
            'sensorsTimeoutThreshold' => $config['sensors']['timeout_threshold'],
            'apiLogKeepDays' => $config['misc']['api_log_keep_days'],
            'preventEventDeletionByManagers' => $config->getBoolean('misc', 'prevent_event_deletion_by_managers'),
            'preventSensorDeletionByManagers' => $config->getBoolean('misc', 'prevent_sensor_deletion_by_managers'),
            'requireEventComment' => $config->getBoolean('misc', 'require_event_comment'),
            'requireFilterDescription' => $config->getBoolean('misc', 'require_filter_description'),
            'archivePrefer' => $config->getBoolean('misc', 'archive_prefer'),
            'archiveMoveDays' => $config['misc']['archive_move_days'],
            'archiveKeepDays' => $config['misc']['archive_keep_days']
        );
    }

    /**
     * Sends a test e-email via a given SMTP server.
     *
     * @param array $data
     * @return Task
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function sendTestMail($data) {
        $this->assureAllowed('update');
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
        $contactService = $this->getServiceManager()->get(ServiceManager::SERVICE_CONTACT);
        $this->log(sprintf('Test E-Mail sent to %s', $data['recipient']), LogEntry::RESOURCE_SETTINGS);
        return $contactService->sendTestMail($this->getSessionUser(), $data['smtpFrom'], $data['recipient'], $data['smtpServer'], $data['smtpPort'], $data['smtpEncryption'], $data['smtpUser'], $data['smtpPassword']);
    }

    public function sendTestEvent() {
        $this->assureAllowed('update');
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
        $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->enqueue(null, Task::TYPE_EVENT_FORWARDER, array('event' => $ev));
        $this->log('Syslog test event forwarded', LogEntry::RESOURCE_SESSIONS);
        return $ev;
    }
}
