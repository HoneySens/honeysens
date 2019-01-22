<?php
namespace HoneySens\app\models;

use HoneySens\app\controllers\System;
use HoneySens\app\models\exceptions\ForbiddenException;
use \Pheanstalk\Pheanstalk;
use \HoneySens\app\models\entities\Sensor;

class BeanstalkService {
	
	protected $pheanstalkInstance = null;
    protected $appConfig = null;
	
	public function __construct($config) {
        $this->appConfig = $config;
		$this->pheanstalkInstance = new Pheanstalk($config['beanstalkd']['host'], $config['beanstalkd']['port']);
	}
	
	public function isAvailable() {
		return $this->pheanstalkInstance->getConnection()->isServiceListening();
	}
	
    /**
     * Schedules a new job that creates a sensor configuration archive.
     */
    public function putSensorConfigCreationJob($sensor, $em) {
        $sensor->setConfigArchiveStatus(Sensor::CONFIG_ARCHIVE_STATUS_SCHEDULED);
        $jobData = $sensor->getState();
        $jobData['cert'] = $sensor->getCert()->getContent();
        $jobData['key'] = $sensor->getCert()->getKey();
        // If this sensor has a custom configuration without a specific firmware configured, we have to rely on the default one
        $jobData['firmware'] = $sensor->getFirmware() != null ? $sensor->getFirmware()->getId() : null;
        if($sensor->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_DEFAULT) {
            $jobData['server_endpoint_host'] = $this->appConfig['server']['host'];
            $jobData['server_endpoint_port_https'] = $this->appConfig['server']['portHTTPS'];
        }
        $jobData['server_endpoint_name'] = $this->appConfig['server']['host'];
        $jobData['proxy_password'] = $sensor->getProxyPassword();
        $this->pheanstalkInstance->useTube('honeysens-sensorcfg')->put(json_encode($jobData));
    }

    public function putUpdateJob() {
        $jobData = array('server_version' => System::VERSION);
        if(file_exists(realpath(APPLICATION_PATH . '/../data/') . '/UPDATE')) throw new ForbiddenException();
        $update_marker = fopen(realpath(APPLICATION_PATH . '/../data/') . '/UPDATE', 'w');
        fclose($update_marker);
        $this->pheanstalkInstance->useTube('honeysens-update')->put(json_encode($jobData));
    }

    /**
     * Pushes an docker image archive with the given name to the registry.
     * Name should be the full name (including tag) of the image, e.g. services/cowrie:1.0.0-amd64
     * The archive path should be the absolute path to the image archive.
     *
     * @param string $name
     * @param string $archivePath
     * @param string $archiveName
     */
    public function putServiceRegistryJob($name, $archivePath, $archiveName) {
        $jobData = array('name' => $name, 'archive_path' => $archivePath, 'archive_name' => $archiveName);
        $this->pheanstalkInstance->useTube('honeysens-service-registry')->put(json_encode($jobData));
    }
}