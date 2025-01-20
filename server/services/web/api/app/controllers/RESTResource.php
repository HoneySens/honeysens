<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\ForbiddenException;
use Respect\Validation\Validator as V;
use Slim\Interfaces\RouteCollectorProxyInterface;

abstract class RESTResource {

    const string HEADER_HMAC = 'x-hs-auth';
    const string HEADER_HMAC_ALGO = 'x-hs-type';
    const string HEADER_SENSOR = 'x-hs-sensor';
    const string HEADER_TIMESTAMP = 'x-hs-ts';
    const string SERVER_TLS_CERT_PATH = '/srv/tls/https.crt';

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * Each controller / REST resource has to supply at least this method
     * to register its own routes with the provided API interface,
     * which is scoped to each controller.
     *
     * @param RouteCollectorProxyInterface $api Controller-scoped interface
     */
    abstract static function registerRoutes(RouteCollectorProxyInterface $api): void;

    /**
     * Given a resource-specific method (typically get/create/update/delete)
     * and a realm (application module, usually the name of the active controller),
     * asserts the required permissions for the currently logged-in user. Raises
     * ForbiddenException in case of missing permissions.
     *
     * @param string $method Resource-specific method name to check permission for
     * @param string|null $realm Application module to check for. If null, defaults to the currently active controller.
     * @throws ForbiddenException
     */
    protected function assureAllowed(string $method, string $realm=null): void {
        if($realm) {
            if(!in_array($method, $_SESSION['user']['permissions'][$realm])) throw new ForbiddenException();
        } else {
            if(!in_array($method, $_SESSION['user']['permissions'][strtolower(str_replace('HoneySens\\app\\controllers\\', '', get_class($this)))])) {
                throw new ForbiddenException();
            }
        }
    }

    /**
     * Reads the given file path and sends it to the client, labeled as $name (shown in browsers).
     * Afterwards, calls the optional $callback function.
     *
     * @param string $path Local file name to send to client
     * @param string $name Display name in browsers that receive the file
     * @param callable|null $callback Optional function to call after the download offering succeeds
     */
    protected function offerFile(string $path, string $name, callable $callback=null): void {
        if(!file_exists($path)) {
            header('HTTP/1.0 400 Bad Request');
            exit;
        }
        session_write_close();
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
            if($callback !== null) call_user_func($callback);
            exit;
        }
    }

    /**
     * Returns a User object for the currently logged in user.
     * In case no user is logged in, this throws an exception.
     * This never returns a user with a guest role.
     *
     * @throws ForbiddenException
     * @throws NotSupported
     */
    public function getSessionUser(): User {
        if(UserRole::from($_SESSION['user']['role']) === UserRole::GUEST) throw new ForbiddenException();
        return $this->em->getRepository('HoneySens\app\models\entities\User')->find($_SESSION['user']['id']);
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
     * @param $method string Requested method to execute (get/create))
     * @param $body string Request body
     * @throws ForbiddenException
     * @throws NotSupported
     */
    protected function validateSensorRequest(string $method, string $body=''): Sensor {
        $headers = $this->getNormalizedRequestHeaders();
        // Check MAC
        if(!V::key(self::HEADER_HMAC_ALGO, V::stringType())
            ->key(self::HEADER_HMAC, V::stringType())
            ->key(self::HEADER_TIMESTAMP, V::intVal())
            ->key(self::HEADER_SENSOR, V::intVal())->validate($headers))
            throw new ForbiddenException();
        $sensor = $this->em->getRepository('HoneySens\app\models\entities\Sensor')->find($headers[self::HEADER_SENSOR]);
        V::objectType()->check($sensor);
        if(!$this->isValidMAC($headers[self::HEADER_HMAC],
            $sensor->secret,
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
     * Sets all required HMAC headers (see validateSensorRequest()) for a response sent to a specific sensor.
     *
     * @param $sensor Sensor to calculate the MAC for
     * @param $method string HTTP request type
     * @param $body string HTTP body (optional)
     */
    protected function setMACHeaders(Sensor $sensor, string $method, string $body=''): void {
        $algo = in_array($this::HEADER_HMAC_ALGO, $_SERVER) ? $_SERVER[$this::HEADER_HMAC_ALGO] : 'sha256';
        $now = time();
        $msg = sprintf('%u %s %s', $now, $method, $body);
        $hmac = hash_hmac($algo, $msg, $sensor->secret, false);
        header(sprintf('%s: %s', self::HEADER_HMAC, $hmac));
        header(sprintf('%s: %s', self::HEADER_HMAC_ALGO, $algo));
        header(sprintf('%s: %s', self::HEADER_TIMESTAMP, $now));
    }

    /**
     * Returns the current server TLS certificate data as a string.
     */
    public function getServerCert(): string {
        return file_get_contents(self::SERVER_TLS_CERT_PATH);
    }

    /**
     * Validates the given MAC for a specific key and request.
     *
     * @param $mac string MAC to validate
     * @param $key string Sensor secret
     * @param $algo string Hashing algorithm to use
     * @param $timestamp int Timestamp as replay protection
     * @param $method string HTTP request type
     * @param $body string HTTP body
     */
    private function isValidMAC(string $mac, string $key, string $algo, int $timestamp, string $method, string $body): bool {
        if($algo !== 'sha256') return false;
        $msg = sprintf('%u %s %s', $timestamp, $method, $body);
        return $mac === hash_hmac($algo, $msg, $key, false);
    }

    /**
     * Normalizes and returns HTTP request headers by converting their keys to lowercase and replacing _ with -.
     */
    private function getNormalizedRequestHeaders(): array {
        $result = [];
        foreach(getallheaders() as $key => $val) {
            $nkey = str_replace('_', '-', strtolower($key));
            $result[$nkey] = $val;
        }
        return $result;
    }
}
