<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

class Settings extends RESTResource {

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
		// Supply SMTP data only to admins
        if($this->getSessionUserID() == null) {
            $settings['smtpEnabled'] = $config->getBoolean('smtp', 'enabled');
			$settings['smtpServer'] = $config['smtp']['server'];
			$settings['smtpPort'] = $config['smtp']['port'];
			$settings['smtpFrom'] = $config['smtp']['from'];
			$settings['smtpUser'] = $config['smtp']['user'];
			$settings['smtpPassword'] = $config['smtp']['password'];
        }
        return $settings;
	}

    /**
     * Updates the system-wide settings.
     * The following parameters are required:
     * - serverHost: The hostname the server is reachable as
     * - serverPortHTTPS: TCP port the server offers its API
     * - smtpServer: IP or hostname of a mail server
     * - smtpFrom: E-Mail address to use as sender of system mails
     * - smtpUser: SMTP Username to authenticate with
     * - smtpPassword: SMTP Password to authenticate with
     * - sensorsUpdateInterval: The delay between status update connection attempts initiated by sensors
     * - sensorsServiceNetwork: The internal network range that sensors should use for service containers
     *
     * @param \stdClass $data
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
	public function update($data) {
		$this->assureAllowed('update');
        // Validation
        V::objectType()
            ->attribute('serverHost', V::stringType())
            ->attribute('serverPortHTTPS', V::intVal()->between(0, 65535))
            ->attribute('smtpEnabled', V::boolType())
            ->attribute('sensorsUpdateInterval', V::intVal()->between(1, 60))
            ->attribute('sensorsServiceNetwork', V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/'))
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
        $config->set('sensors', 'update_interval', $data->sensorsUpdateInterval);
        $config->set('sensors', 'service_network', $data->sensorsServiceNetwork);
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
            'sensorsUpdateInterval' => $config['sensors']['update_interval'],
            'sensorsServiceNetwork' => $config['sensors']['service_network']
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
}
