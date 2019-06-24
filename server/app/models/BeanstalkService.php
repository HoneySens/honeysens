<?php
namespace HoneySens\app\models;

use HoneySens\app\controllers\System;
use HoneySens\app\models\exceptions\ForbiddenException;
use \Pheanstalk\Pheanstalk;

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

    public function putUpdateJob() {
        $jobData = array('server_version' => System::VERSION);
        if(file_exists(realpath(APPLICATION_PATH . '/../data/') . '/UPDATE')) throw new ForbiddenException();
        $update_marker = fopen(realpath(APPLICATION_PATH . '/../data/') . '/UPDATE', 'w');
        fclose($update_marker);
        $this->pheanstalkInstance->useTube('honeysens-update')->put(json_encode($jobData));
    }
}