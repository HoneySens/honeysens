<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\Task;
use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

class Settings extends RESTResource {

    // Reusable specifications for external network connections (SMTP, LDAP etc.)
    const ENCRYPTION_NONE = 0;
    const ENCRYPTION_STARTTLS = 1;
    const ENCRYPTION_TLS = 2;

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/settings', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Settings($em, $services, $config);
            $settings = $controller->get();
            echo json_encode($settings);
        });

        $app->put('/api/settings', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Settings($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $settingsData = json_decode($request);
            $settings = $controller->update($settingsData);
            echo json_encode($settings);
        });

        $app->post('/api/settings/testmail', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Settings($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $data = json_decode($request);
            $controller->sendTestMail($data);
            echo json_encode([]);
        });

        $app->post('/api/settings/testevent', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Settings($em, $services, $config);
            echo json_encode($controller->sendTestEvent());
        });
    }

    /**
     * Returns the current system-wide settings.
     *
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get() {
        $this->assureAllowed('all');
        // TODO This silently returns nothing if the config is invalid
        $config = $this->getConfig();
        $caCert = file_get_contents(APPLICATION_PATH . '/../data/CA/ca.crt');
        $settings = array(
            'id' => 0,
            'serverHost' => $config['server']['host'],
            'serverPortHTTPS' => $config['server']['portHTTPS'],
            'sensorsUpdateInterval' => $config['sensors']['update_interval'],
            'sensorsServiceNetwork' => $config['sensors']['service_network'],
            'caFP' => openssl_x509_fingerprint($caCert),
            'caExpire' => openssl_x509_parse($caCert)['validTo_time_t']
        );
        // Settings only relevant to admins
        if($this->getSessionUserID() == null) {
            // SMTP
            $settings['smtpEnabled'] = $config->getBoolean('smtp', 'enabled');
            $settings['smtpServer'] = $config['smtp']['server'];
            $settings['smtpPort'] = $config['smtp']['port'];
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
            $settings['restrictManagerRole'] = $config->getBoolean('misc', 'restrict_manager_role');
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
     * - restrictManagerRole: Enables or disable permission restrictions for managers
     *
     * Optional parameters:
     * - smtpServer: IP or hostname of a mail server
     * - smtpPort: TCP port to use for SMTP connections
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
     * @param \stdClass $data
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update($data) {
        $this->assureAllowed('update');
        // Validation
        V::objectType()
            ->attribute('serverHost', V::stringType())
            ->attribute('serverPortHTTPS', V::intVal()->between(0, 65535))
            ->attribute('smtpEnabled', V::boolType())
            ->attribute('ldapEnabled', V::boolType())
            ->attribute('syslogEnabled', V::boolType())
            ->attribute('sensorsUpdateInterval', V::intVal()->between(1, 60))
            ->attribute('sensorsServiceNetwork', V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/'))
            ->attribute('restrictManagerRole', V::boolType())
            ->check($data);
        if($data->smtpEnabled) {
           V::attribute('smtpServer', V::stringType())
               ->attribute('smtpPort', V::intVal()->between(0, 65535))
               ->attribute('smtpFrom', V::email())
               ->attribute('smtpUser', V::optional(V::stringType()))
               ->attribute('smtpPassword', V::stringType())
               ->check($data);
        } else {
           V::attribute('smtpServer', V::optional(V::stringType()))
               ->attribute('smtpPort', V::optional(V::intVal()->between(0, 65535)))
               ->attribute('smtpFrom', V::optional(V::email()))
               ->attribute('smtpUser', V::optional(V::stringType()))
               ->attribute('smtpPassword', V::optional(V::stringType()))
               ->check($data);
        }
        if($data->ldapEnabled) {
            V::attribute('ldapServer', V::stringType())
                ->attribute('ldapPort', V::intVal()->between(0, 65535))
                ->attribute('ldapEncryption', V::intVal()->between(0, 2))
                ->attribute('ldapTemplate', V::stringType())
                ->check($data);
        } else {
            V::attribute('ldapServer', V::optional(V::stringType()))
                ->attribute('ldapPort', V::optional(V::intVal()->between(0, 65535)))
                ->attribute('ldapEncryption', V::optional(V::intVal()->between(0, 2)))
                ->attribute('ldapTemplate', V::optional(V::stringType()))
                ->check($data);
        }
        if($data->syslogEnabled) {
            V::attribute('syslogServer', V::stringType())
                ->attribute('syslogPort', V::intVal()->between(0, 65535))
                ->attribute('syslogTransport', V::intVal()->between(0, 1))
                ->attribute('syslogFacility', V::oneOf(V::intVal()->between(0, 11), V::intVal()->between(16, 23)))
                ->attribute('syslogPriority', V::oneOf(V::intVal()->between(2, 4), V::intVal()->between(6, 7)))
                ->check($data);
        } else {
            V::attribute('syslogServer', V::optional(V::stringType()))
                ->attribute('syslogPort', V::optional(V::intVal()->between(0, 65535)))
                ->attribute('syslogTransport', V::optional(V::intVal()->between(0, 1)))
                ->attribute('syslogFacility', V::optional(V::oneOf(V::intVal()->between(0, 11), V::intVal()->between(16, 23))))
                ->attribute('syslogPriority', V::optional(V::intVal()->between(0, 7)))
                ->check($data);
        }
        // Persistence
        $config = $this->getConfig();
        $config->set('server', 'host', $data->serverHost);
        $config->set('server', 'portHTTPS', $data->serverPortHTTPS);
        $config->set('smtp', 'enabled', $data->smtpEnabled ? 'true' : 'false');
        $config->set('smtp', 'server', $data->smtpServer);
        $config->set('smtp', 'port', $data->smtpPort);
        $config->set('smtp', 'from', $data->smtpFrom);
        $config->set('smtp', 'user', $data->smtpUser);
        $config->set('smtp', 'password', $data->smtpPassword);
        $config->set('ldap', 'enabled', $data->ldapEnabled ? 'true' : 'false');
        $config->set('ldap', 'server', $data->ldapServer);
        $config->set('ldap', 'port', $data->ldapPort);
        $config->set('ldap', 'encryption', $data->ldapEncryption);
        $config->set('ldap', 'template', $data->ldapTemplate);
        $config->set('syslog', 'enabled', $data->syslogEnabled ? 'true' : 'false');
        $config->set('syslog', 'server', $data->syslogServer);
        $config->set('syslog', 'port', $data->syslogPort);
        $config->set('syslog', 'transport', $data->syslogTransport);
        $config->set('syslog', 'facility', $data->syslogFacility);
        $config->set('syslog', 'priority', $data->syslogPriority);
        $config->set('sensors', 'update_interval', $data->sensorsUpdateInterval);
        $config->set('sensors', 'service_network', $data->sensorsServiceNetwork);
        $config->set('misc', 'restrict_manager_role', $data->restrictManagerRole ? 'true' : 'false');
        $config->save();
        $this->getEntityManager()->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "settings"');
        return array(
            'id' => 0,
            'serverHost' => $config['server']['host'],
            'serverPortHTTPS' => $config['server']['portHTTPS'],
            'smtpEnabled' => $config->getBoolean('smtp', 'enabled'),
            'smtpServer' => $config['smtp']['server'],
            'smtpPort' => $config['smtp']['port'],
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
            'restrictManagerRole' => $config->getBoolean('misc', 'restrict_manager_role')
        );
    }

    public function sendTestMail($data) {
        $this->assureAllowed('update');
        // Validation
        V::objectType()
            ->attribute('recipient', V::stringType())
            ->attribute('smtpServer', V::stringType())
            ->attribute('smtpPort', V::intVal()->between(0, 65535))
            ->attribute('smtpUser', V::optional(V::stringType()))
            ->attribute('smtpFrom', V::optional(V::stringType()))
            ->attribute('smtpPassword', V::optional(V::stringType()))
            ->check($data);
        // Send mail
        $contactService = $this->getServiceManager()->get(ServiceManager::SERVICE_CONTACT);
        $contactService->sendTestMail($data->recipient, $data->smtpServer, $data->smtpPort, $data->smtpUser, $data->smtpPassword, $data->smtpFrom);
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
        return $ev;
    }
}
