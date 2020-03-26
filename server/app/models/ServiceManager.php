<?php
namespace HoneySens\app\models;

use Doctrine\ORM\EntityManager;
use Exception;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

/**
 * Central service management object that acts as a proxy to all the service instances.
 * It lazily instances services whenever necessary.
 */
class ServiceManager {

    const SERVICE_CONTACT = 0;
    const SERVICE_ENTITY_UPDATE = 1;
    const SERVICE_REGISTRY = 2;
    const SERVICE_TASK = 3;

    private $config = null;
    private $em = null;
    private $services = array();

    public function __construct(ConfigParser $config, EntityManager $em) {
        $this->config = $config;
        $this->em = $em;
    }

    public function get($serviceID) {
        if($serviceID < 0 || $serviceID > 4) throw new Exception('Illegal service requested (ID' . $serviceID . ')');
        if(!array_key_exists($serviceID, $this->services)) {
            $this->services[$serviceID] = $this->instantiate($serviceID);
        }
        return $this->services[$serviceID];
    }

    private function instantiate($serviceID) {
        switch($serviceID) {
            case 0: return new ContactService();
            case 1: return new EntityUpdateService();
            case 2: return new RegistryService($this->config);
            case 3: return new TaskService($this->config, $this->em);
            default: return null;
        }
    }
}