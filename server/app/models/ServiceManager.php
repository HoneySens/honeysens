<?php
namespace HoneySens\app\models;

/**
 * Central service management object that acts as a proxy to all the service instances.
 * It also lazily instances services whenever necessary.
 */
class ServiceManager {

    const SERVICE_BEANSTALK = 0;
    const SERVICE_CONTACT = 1;
    const SERVICE_ENTITY_UPDATE = 2;
    const SERVICE_REGISTRY = 3;

    protected $services = array();
    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function get($serviceID) {
        if($serviceID < 0 || $serviceID > 3) throw new \Exception('Illegal service requested (ID' . $serviceID . ')');
        if(!array_key_exists($serviceID, $this->services)) {
            $this->services[$serviceID] = $this->instantiate($serviceID, $this->config);
        }
        return $this->services[$serviceID];
    }

    private function instantiate($serviceID, $config) {
        switch($serviceID) {
            case 0: return new BeanstalkService($config);
            case 1: return new ContactService();
            case 2: return new EntityUpdateService();
            case 3: return new RegistryService($config);
            default: return null;
        }
    }
}