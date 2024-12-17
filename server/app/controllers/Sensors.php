<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\SensorEAPOLMode;
use HoneySens\app\models\constants\SensorNetworkIPMode;
use HoneySens\app\models\constants\SensorNetworkMACMode;
use HoneySens\app\models\constants\SensorProxyMode;
use HoneySens\app\models\constants\SensorServerEndpointMode;
use HoneySens\app\models\constants\SensorStatusFlag;
use HoneySens\app\services\dto\SensorParams;
use HoneySens\app\services\dto\SensorStatus;
use HoneySens\app\services\SensorsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Sensors extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('[/{id:\d+}]', [Sensors::class, 'getSensors']);
        $api->post('', [Sensors::class, 'createSensor']);
        $api->put('/{id:\d+}', [Sensors::class, 'updateSensor']);
        $api->delete('/{id:\d+}', [Sensors::class, 'deleteSensor']);
        $api->get('/status/by-sensor/{id:\d+}', [Sensors::class, 'getStatus']);
        $api->get('/config/{id:\d+}', [Sensors::class, 'requestConfigDownload']);
        $api->get('/firmware', [Sensors::class, 'getFirmware']);
        $api->post('/status', [Sensors::class, 'recordSensorStatus']);
    }

    public function getSensors(Response $response, SensorsService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getSensors($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function createSensor(Request $request, Response $response, SensorsService $service): Response {
        $this->assureAllowed('create');
        $sensorData = $this->validateSensorParams($request->getParsedBody());
        $sensor = $service->createSensor($this->getSessionUser(), $sensorData);
        $result = $service->getSensorState($sensor);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function updateSensor(Request $request, Response $response, SensorsService $service, int $id): Response {
        $this->assureAllowed('update');
        $sensor = $service->updateSensor($id, $this->getSessionUser(), $this->validateSensorParams($request->getParsedBody(), true));
        $result = $service->getSensorState($sensor);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function deleteSensor(Request $request, Response $response, SensorsService $service, int $id): Response {
        $this->assureAllowed('delete');
        $criteria = $request->getParsedBody();
        try {
            $this->assureAllowed('delete', 'events');
            $archive = V::key('archive', V::boolType())->validate($criteria) && $criteria['archive'];
        } catch(\Exception $e) {
            // In case the current user can't delete events, force archiving
            $this->assureAllowed('archive', 'events');
            $archive = true;
        }
        $service->deleteSensor($id, $archive, $this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function getStatus(Response $response, SensorsService $service, int $id): Response {
        $this->assureAllowed('get');
        $result = $service->getStatus($id, $this->getSessionUser());
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function requestConfigDownload(Response $response, SensorsService $service, int $id): Response {
        $this->assureAllowed('downloadConfig');
        $task = $service->requestConfigDownload($id, $this->getSessionUser());
        $response->getBody()->write(json_encode($task->getState()));
        return $response;
    }

    /**
     * This resource is used by authenticated sensors to receive firmware download details.
     * The return value is an array with platform names as keys and their respective access URIs as value.
     */
    public function getFirmware(Response $response, SensorsService $service): Response {
        $sensor = $this->validateSensorRequest('get', '');
        $body = json_encode($service->getFirmwareURIs($sensor));
        $this->setMACHeaders($sensor, 'get', $body);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * Polling endpoint for sensors to send status data and receive their current configuration.
     * The given JSON data should have the following attributes:
     *  - status: The actual status data as JSON object, encoded in base64
     *
     * The following attributes are optional:
     * - srv_crt_fp: Sensor's current server TLS certificate fingerprint
     * - eapol_ca_crt_fp: Sensor's current EAPOL CA certificate fingerprint
     * - eapol_client_crt_fp: Sensor's current EAPOL client certificate fingerprint
     */
    public function recordSensorStatus(Request $request, Response $response, SensorsService $service): Response {
        $requestBody = $request->getBody()->getContents();
        $sensor = $this->validateSensorRequest('create', $requestBody);
        // Parse sensor request as JSON even if no correct Content-Type header is set
        V::json()->check($requestBody);
        $data = json_decode($requestBody, true);
        V::arrayType()
            ->key('status', V::stringType())
            ->key('srv_crt_fp', V::stringType(), false)
            ->key('eapol_ca_crt_fp', V::optional(V::stringType()), false)
            ->key('eapol_client_crt_fp', V::optional(V::stringType()), false)
            ->check($data);
        $statusData = base64_decode($data['status']);
        V::json()->check($statusData);
        $sensorStatus = $this->validateSensorStatus(json_decode($statusData, true));
        $optionalParams = array();
        if(array_key_exists('srv_crt_fp', $data)) $optionalParams['srvCrtFp'] = $data['srv_crt_fp'];
        if(array_key_exists('eapol_ca_crt_fp', $data)) $optionalParams['eapolCaCrtFp'] = $data['eapol_ca_crt_fp'];
        if(array_key_exists('eapol_client_crt_fp', $data)) $optionalParams['eapolClientCrtFp'] = $data['eapol_client_crt_fp'];
        $sensorData = $service->poll($sensor, $sensorStatus, $this->getServerCert(), ...$optionalParams);
        $body = json_encode($sensorData);
        $this->setMACHeaders($sensor, 'create', $body);
        $response->getBody()->write($body);
        return $response;
    }

    public function validateSensorParams(array $data, bool $isUpdate = false): SensorParams {
        $result = new SensorParams();
        V::arrayType()
            ->key('name', V::alnum('_-. ')->length(1, 50))
            ->key('location', V::stringType()->length(0, 255))
            ->key('division', V::intVal())
            ->key('eapol_mode', V::intVal()->between(0, 4))
            ->key('server_endpoint_mode', V::intVal()->between(0, 1))
            ->key('network_ip_mode', V::intVal()->between(0, 2))
            ->key('network_mac_mode', V::intVal()->between(0, 1))
            ->key('proxy_mode', V::intVal()->between(0, 1))
            ->key('update_interval', V::optional(V::intVal()->between(1, 60)))
            ->key('service_network', V::optional(V::stringType()->length(9, 18)))
            ->key('firmware', V::optional(V::intVal()))
            ->key('services', V::arrayType()->each(V::arrayType()
                ->key('service', V::intType())
                ->key('revision', V::nullType())), $isUpdate)  // Currently unused
            ->check($data);
        $result->name = $data['name'];
        $result->location = $data['location'];
        $result->divisionID = intval($data['division']);
        $result->eapolMode = SensorEAPOLMode::from(intval($data['eapol_mode']));
        $result->serverEndpointMode = SensorServerEndpointMode::from(intval($data['server_endpoint_mode']));
        $result->ipMode = SensorNetworkIPMode::from(intval($data['network_ip_mode']));
        $result->macMode = SensorNetworkMACMode::from(intval($data['network_mac_mode']));
        $result->proxyMode = SensorProxyMode::from(intval($data['proxy_mode']));
        $result->updateInterval = $data['update_interval'] !== null ? intval($data['update_interval']) : null;
        $result->serviceNetwork = $data['service_network'];
        $result->firmwareID = $data['firmware'] !== null ? intval($data['firmware']) : null;
        if($result->serverEndpointMode === SensorServerEndpointMode::CUSTOM) {
            V::key('server_endpoint_host', V::stringType()->ip())
                ->key('server_endpoint_port_https', V::intVal()->between(1, 65535))
                ->check($data);
            $result->serverEndpointHost = $data['server_endpoint_host'];
            $result->serverEndpointPort = intval($data['server_endpoint_port_https']);
        }
        switch($result->ipMode) {
            case SensorNetworkIPMode::STATIC:
                V::key('network_ip_address', V::stringType()->ip())
                    ->key('network_ip_netmask', V::stringType()->ip())
                    ->key('network_ip_gateway', V::optional(V::stringType()->ip()))
                    ->key('network_ip_dns', V::optional(V::stringType()->ip()))
                    ->check($data);
                $result->ipAddress = $data['network_ip_address'];
                $result->ipNetmask = $data['network_ip_netmask'];
                $result->ipGateway = $data['network_ip_gateway'];
                $result->ipDNS = $data['network_ip_dns'];
                break;
            case SensorNetworkIPMode::DHCP:
                V::key('network_dhcp_hostname',
                    V::optional(V::alnum('-.')->lowercase()->length(1, 253)))
                    ->check($data);
                $result->dhcpHostname = strlen($data['network_dhcp_hostname']) === 0 ? null : $data['network_dhcp_hostname'];
                break;
        }
        if($result->macMode === SensorNetworkMACMode::CUSTOM) {
            V::key('network_mac_address', V::stringType()->macAddress())->check($data);
            $result->macAddress = $data['network_mac_address'];
        }
        if($result->proxyMode === SensorProxyMode::ENABLED) {
            V::key('proxy_host', V::stringType())
                ->key('proxy_port', V::intVal()->between(0, 65535))
                ->key('proxy_user', V::optional(V::stringType()))
                ->check($data);
            $result->proxyHost = $data['proxy_host'];
            $result->proxyPort = intval($data['proxy_port']);
            // Only parse proxy_password in case a proxy_user has been given
            $proxyUser = $data['proxy_user'];
            if($proxyUser !== null && strlen($proxyUser) > 0) {
                $result->proxyUser = $data['proxy_user'];
                if(V::key('proxy_password', V::optional(V::stringType()))->validate($data)) {
                    $result->proxyPassword = $data['proxy_password'];
                }
            }
        }
        if($result->eapolMode !== SensorEAPOLMode::DISABLED) {
            V::key('eapol_identity', V::stringType()->length(1, 512))->check($data);
            $result->eapolIdentity = $data['eapol_identity'];
            if($result->eapolMode === SensorEAPOLMode::MD5) {
                if(!$isUpdate) {
                    // On creation: Require password
                    V::key('eapol_password', V::stringType()->length(1, 512))->check($data);
                    $result->eapolPassword = $data['eapol_password'];
                } elseif(V::key('eapol_password', V::stringType()->length(1, 512))->validate($data)) {
                    // On updates: Password update is optional, requires eapol_password to be set
                    $result->eapolPassword = $data['eapol_password'];
                }
            } else {
                if(V::key('eapol_ca_cert', V::optional(V::stringType()))->validate($data)) {
                    $result->eapolCACert = $data['eapol_ca_cert'];
                }
                if($result->eapolMode === SensorEAPOLMode::TLS) {
                    $eapolClientValidator = V::key('eapol_client_cert', V::stringType())
                        ->key('eapol_client_key', V::stringType())
                        ->key('eapol_client_key_password', V::optional(V::stringType()->length(1, 512)));
                    if(!$isUpdate) {
                        // On creation: eapol_client_* fields are required
                        $eapolClientValidator->check($data);
                        $result->eapolClientCert = $data['eapol_client_cert'];
                        $result->eapolClientKey = $data['eapol_client_key'];
                        $result->eapolClientKeyPassword = $data['eapol_client_key_password'];
                    } elseif($eapolClientValidator->validate($data)) {
                        // On updates: Update TLS data only if eapol_client_* fields are set
                        $result->eapolClientCert = $data['eapol_client_cert'];
                        $result->eapolClientKey = $data['eapol_client_key'];
                        $result->eapolClientKeyPassword = $data['eapol_client_key_password'];
                    }
                } else {
                    V::key('eapol_anon_identity', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    $result->eapolAnonIdentity = $data['eapol_anon_identity'];
                    if(!$isUpdate) {
                        // On creation: Require password
                        V::key('eapol_password', V::optional(V::stringType()->length(1, 512)))->check($data);
                        $result->eapolPassword = $data['eapol_password'];
                    }  elseif(V::key('eapol_password', V::optional(V::stringType()->length(1, 512)))->validate($data)) {
                        // On updates: Password update is optional, requires eapol_password to be set
                        $result->eapolPassword = $data['eapol_password'];
                    }
                }
            }
        }
        if($isUpdate) $result->services = $data['services'];
        return $result;
    }

    public function validateSensorStatus(array $data): SensorStatus {
        $result = new SensorStatus();
        V::arrayType()
            ->key('timestamp', V::intVal())
            ->key('status', V::intVal()->between(0, 2))
            ->key('ip', V::stringType()->ip())
            ->key('free_mem', V::intVal())
            ->key('disk_usage', V::intVal())
            ->key('disk_total', V::intVal())
            ->key('sw_version', V::stringType())
            ->key('service_status', V::arrayType()->each(V::intVal()->between(0, 2)))
            ->check($data);
        $result->timestamp = intval($data['timestamp']);
        $result->status = SensorStatusFlag::from(intval($data['status']));
        $result->ip = $data['ip'];
        $result->freeMem = $data['free_mem'];
        $result->diskUsage = $data['disk_usage'];
        $result->diskSize = $data['disk_total'];
        $result->swVersion = $data['sw_version'];
        $result->serviceStatus = $data['service_status'];
        return $result;
    }
}