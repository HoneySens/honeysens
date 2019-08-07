<?php
namespace HoneySens\app\models;

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
}