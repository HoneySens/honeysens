<?php
namespace HoneySens\app\controllers;
use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

abstract class RESTResource {

    const HEADER_HMAC = 'x-hs-auth';
    const HEADER_HMAC_ALGO = 'x-hs-type';
    const HEADER_SENSOR = 'x-hs-sensor';
    const HEADER_TIMESTAMP = 'x-hs-ts';

    protected $entityManager;
    protected $services;
    protected $config;

    public function __construct(EntityManager $entityManager, $services, $config) {
        $this->entityManager = $entityManager;
        $this->services = $services;
        $this->config = $config;
    }

    protected function getEntityManager() {
        return $this->entityManager;
    }

    protected function getServiceManager() {
        return $this->services;
    }

    protected function getConfig() {
        return $this->config;
    }

    abstract static function registerRoutes($app, $em, $services, $config);

    protected function assureAllowed($method, $realm=null) {
        if($realm) {
            if(!in_array($method, $_SESSION['user']['permissions'][$realm])) throw new ForbiddenException();
        } else {
            if(!in_array($method, $_SESSION['user']['permissions'][strtolower(str_replace('HoneySens\\app\\controllers\\', '', get_class($this)))])) {
                throw new ForbiddenException();
            }
        }
    }

    protected function offerFile($path, $name, $callback=null) {
        if(!file_exists($path)) {
            header('HTTP/1.0 400 Bad Request');
            exit;
        }
        @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 'Off');
        set_time_limit(0);
        ob_end_clean();
        if(file_exists($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $name);
            header('Expires: 0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            if($callback != null) call_user_func($callback);
            exit;
        }
    }

    /**
     * Computes an ArrayCollection update with a list of entity IDs. Returns an array of the form
     * array('add' => array(), 'update' => array(), 'remove' => array())
     * that specifies the required tasks to complete the operation.
     *
     * @param $collection ArrayCollection of the entities to be updated
     * @param $ids Array of entity IDs to update the collection with
     * @param $repository Repository to fetch entities from
     * @return array Specifies tasks to perform to perform the operation
     */
    protected function updateCollection($collection, &$ids, $repository) {
        $tasks = array('add' => array(), 'update' => array(), 'remove' => array());
        foreach($collection as $entity) {
            if(in_array($entity->getId(), $ids)) {
                $tasks['update'][] = $entity;
                if(($key = array_search($entity->getId(), $ids)) !== false) {
                    unset($ids[$key]);
                    $ids = array_values($ids);
                }
            } else {
                $tasks['remove'][] = $entity;
            }
        }
        foreach($ids as $entityId) {
            $entity = $repository->find($entityId);
            if($entity) $tasks['add'][] = $entity;
        }
        return $tasks;
    }

    /**
     * Returns the user id of the currently logged in user or null in case of an admin user.
     * This means that for both admin and guest users null is returned, which means that an additional permission check is
     * required. This step is usually done inside of the resource classes/controllers.
     *
     * @return null|integer
     */
    public function getSessionUserID() {
        if($_SESSION['user']['role'] == User::ROLE_ADMIN) {
            return null;
        } else return $_SESSION['user']['id'];
    }

    /**
     * Returns the User object of the currently logged in user (or null if no user is logged in).
     */
    public function getSessionUser() {
        if($_SESSION['user']['role'] == User::ROLE_GUEST) return null;
        return $this->entityManager->getRepository('HoneySens\app\models\entities\User')->find($_SESSION['user']['id']);
    }

    /**
     * Validates the given MAC for a specific key and request.
     *
     * @param $mac string The MAC to validate
     * @param $key string
     * @param $algo string Hashing algorithm to use
     * @param $timestamp int
     * @param $method string HTTP request type
     * @param $body string HTTP body
     * @return bool
     */
    protected function isValidMAC($mac, $key, $algo, $timestamp, $method, $body) {
        if(!in_array($algo, array('sha256'))) return false;
        $msg = sprintf('%u %s %s', $timestamp, $method, $body);
        return $mac === hash_hmac($algo, $msg, $key, false);
    }

    /**
     * Validates the current HTTP request against known sensors.
     * We authenticate sensors via HMACs, which are provided via the following headers:
     * X-HS-Type: HMAC algorithm to use
     * X-HS-Auth: HMAC as received from the client in lowercase hexits
     * X-HS-Sensor: Sensor ID
     * X-HS-TS: Unix timestamp of this request
     *
     * Returns Sensor instance in case of successful authentication or throws an exception for invalid requests.
     *
     * @param $method string
     * @param $body string
     * @return Sensor
     */
    protected function validateSensorRequest($method, $body='') {
        $headers = $this->getNormalizedRequestHeaders();
        // Check MAC
        if(!V::key(self::HEADER_HMAC_ALGO, V::stringType())
            ->key(self::HEADER_HMAC, V::stringType())
            ->key(self::HEADER_TIMESTAMP, V::intVal())
            ->key(self::HEADER_SENSOR, V::intVal())->validate($headers))
            throw new ForbiddenException();
        $sensor = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Sensor')->find($headers[self::HEADER_SENSOR]);
        V::objectType()->check($sensor);
        if(!$this->isValidMAC($headers[self::HEADER_HMAC],
            $sensor->getSecret(),
            $headers[self::HEADER_HMAC_ALGO],
            intval($headers[self::HEADER_TIMESTAMP]),
            $method,
            $body)) throw new ForbiddenException();
        // Verify timestamp, permit a 60 second window of time drift
        $timestamp = intval($headers[self::HEADER_TIMESTAMP]);
        if(abs(time() - $timestamp) > 60) throw new ForbiddenException();
        return $sensor;
    }

    /**
     * Sets the required HMAC headers (see validateSensorRequest()) for a response sent to a specific sensor.
     *
     * @param $sensor Sensor to calculate the MAC for
     * @param $method string HTTP request type
     * @param $body string HTTP body (optional)
     */
    protected function setMACHeaders($sensor, $method, $body='') {
        $algo = in_array($this::HEADER_HMAC_ALGO, $_SERVER) ? $_SERVER[$this::HEADER_HMAC_ALGO] : 'sha256';
        $now = time();
        $msg = sprintf('%u %s %s', $now, $method, $body);
        $hmac = hash_hmac($algo, $msg, $sensor->getSecret(), false);
        header(sprintf('%s: %s', self::HEADER_HMAC, $hmac));
        header(sprintf('%s: %s', self::HEADER_HMAC_ALGO, $algo));
        header(sprintf('%s: %s', self::HEADER_TIMESTAMP, $now));
    }

    /**
     * Normalizes and returns HTTP request headers by converting their keys to lowercase and replacing _ with -.
     *
     * @return array
     */
    protected function getNormalizedRequestHeaders() {
        $result = [];
        foreach(getallheaders() as $key => $val) {
            $nkey = str_replace('_', '-', strtolower($key));
            $result[$nkey] = $val;
        }
        return $result;
    }

    /**
     * Returns the current server TLS certificate data as a string.
     */
    public function getServerCert() {
        return file_get_contents('/srv/tls/https.crt');
    }

    /**
     * Retrieves the log service and records a log entry, references the session user by default.
     */
    public function log($message, $resourceType, $resourceID=null, $userID=null) {
        if($userID === null) {
            $sessionUser = $this->getSessionUser();
            $userID = $sessionUser != null ? $sessionUser->getId() : null;
        }
        $this->services->get(ServiceManager::SERVICE_LOG)->log($message, $resourceType, $resourceID, $userID);
    }
}
