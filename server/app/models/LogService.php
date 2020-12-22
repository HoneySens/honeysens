<?php
namespace HoneySens\app\models;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\LogEntry;

class LogService {

    private $em;
    private $services;

    public function __construct($services, EntityManager $em) {
        $this->services = $services;
        $this->em = $em;
    }

    public function isEnabled() {
        return getenv('API_LOG') === 'true';
    }

    public function log($message, $resourceType, $resourceID=null, $userID=null) {
        if($this->isEnabled()) {
            $logEntry = new LogEntry();
            $logEntry->setTimestamp(new \DateTime())
                ->setMessage($message)
                ->setResourceType($resourceType)
                ->setResourceID($resourceID)
                ->setUserID($userID);
            $this->em->persist($logEntry);
            $this->em->flush();
        }
    }
}