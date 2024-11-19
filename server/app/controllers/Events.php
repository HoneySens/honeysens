<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\EventStatus;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\services\dto\EventFilterConditions;
use HoneySens\app\services\EventsService;
use HoneySens\app\services\ResponseFormat;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use Slim\Interfaces\RouteCollectorProxyInterface;

class Events extends RESTResource {

    static function registerRoutes(RouteCollectorProxyInterface $api): void {
        $api->get('[/{id:\d+}]', [Events::class, 'getEvents']);
        $api->post('', [Events::class, 'createEvent']);
        $api->put('[/{id:\d+}]', [Events::class, 'updateEvent']);
        $api->delete('', [Events::class, 'deleteEvent']);
    }

    public function getEvents(Request $request, Response $response, EventsService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $queryParams = $request->getQueryParams();
        if($id !== null) $queryParams['id'] = $id;
        $optionalParams = array();
        $filterConditions = $this::validateEventFilterConditions($this->getSessionUser(), $queryParams);
        if(array_key_exists('page', $queryParams) && array_key_exists('per_page', $queryParams)) {
            V::key('page', V::intVal())->key('per_page', V::intVal()->between(1, 512))->check($queryParams);
            $optionalParams['page'] = $queryParams['page'];
            $optionalParams['perPage'] = $queryParams['per_page'];
        }
        if(array_key_exists('format', $queryParams)) {
            V::key('format', V::stringType())->check($queryParams);
            $format = ResponseFormat::tryFrom($queryParams['format']);
            if($format !== null) $optionalParams['format'] = $format;
        }
        $result = $service->getEvents($this->getSessionUser(), $filterConditions, ...$optionalParams);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function createEvent(Request $request, Response $response, EventsService $service): Response {
        $requestBody = $request->getBody()->getContents();
        $sensor = $this->validateSensorRequest('create', $requestBody);
        // Parse sensor request as JSON even if no correct Content-Type header is set
        $requestBody = json_decode($requestBody, true);
        $eventsData = $this->validateEvents($requestBody);
        $service->createEvent($sensor, $eventsData);
        $this->setMACHeaders($sensor, 'create');
        return $response;
    }

    public function updateEvent(Request $request, Response $response, EventsService $service, ?int $id = null): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        if($id !== null) $data['id'] = $id;
        $updateParams = array();
        if(array_key_exists('new_status', $data)) {
            V::key('new_status', V::intVal()->between(0, 3))->check($data);
            $updateParams['newStatus'] = EventStatus::tryFrom($data['new_status']);
        }
        if(array_key_exists('new_comment', $data)) {
            V::key('new_comment', V::stringType()->length(0, 65535))->check($data);
            $updateParams['newComment'] = $data['new_comment'];
        }
        $filterConditions = $this::validateEventFilterConditions($this->getSessionUser(), $data);
        $service->updateEvent($filterConditions, ...$updateParams);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function deleteEvent(Request $request, Response $response, EventsService $service): Response {
        // In case the current user can't delete events, force archiving
        $data = $request->getParsedBody();
        try {
            $this->assureAllowed('delete');
            $archive = V::key('archive', V::boolType())->validate($data) && $data['archive'];
        } catch (\Exception $e) {
            $this->assureAllowed('archive');
            $archive = true;
        }
        $filterConditions = $this::validateEventFilterConditions($this->getSessionUser(), $data);
        $service->deleteEvent($filterConditions, $archive);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public static function validateEventFilterConditions(User $user, array $data): EventFilterConditions {
        $result = new EventFilterConditions();
        if($user->role !== UserRole::ADMIN) $result->user = $user;
        if(array_key_exists('archived', $data)) {
            $result->archived = V::key('archived', V::trueVal())->validate($data) && $data['archived'];
        }
        if(array_key_exists('division', $data)) {
            V::key('division', V::intVal())->check($data);
            $result->divisionID = intval($data['division']);
        }
        if(array_key_exists('lastID', $data)) {
            // TODO lastID is only part of the criteria submitted by the state controller, regular requests use last_id and are ignored
            V::key('lastID', V::intVal())->check($data);
            $result->lastID = intval($data['lastID']);
        }
        if(array_key_exists('filter', $data)) {
            V::key('filter', V::stringType())->check($data);
            $result->filter = $data['filter'];
        }
        if(array_key_exists('sort_by', $data) && array_key_exists('order', $data)) {
            V::key('sort_by', V::in(['id', 'sensor', 'timestamp', 'classification', 'source', 'summary', 'status', 'comment']))
                ->key('order', V::in(['asc', 'desc']))
                ->check($data);
            $result->sortBy = $data['sort_by'];
            $result->sortOrder = $data['order'];
        }
        if(array_key_exists('division', $data)) {
            V::key('division', V::intVal())->check($data);
            $result->divisionID = intval($data['division']);
        }
        if(array_key_exists('sensor', $data)) {
            V::key('sensor', V::intVal())->check($data);
            $result->sensorID = intval($data['sensor']);
        }
        if(array_key_exists('classification', $data)) {
            V::key('classification', V::intVal()->between(0, 4))->check($data);
            $result->classification = intval($data['classification']);
        }
        if(array_key_exists('status', $data)) {
            V::key('status', V::stringType())->check($data);
            if(str_contains($data['status'], ',')) {
                $status = array_map(function($v) {
                    return intval($v);
                }, explode(',', $data['status']));
                V::arrayVal()->each(V::intVal()->between(0, 3))->check($status);
            } else {
                V::intVal()->between(0, 3)->check($data['status']);
                $status = array(intval($data['status']));
            }
            $result->status = $status;
        }
        if(array_key_exists('fromTS', $data)) {
            V::key('fromTS', V::intVal())->check($data);
            $result->fromTS = intval($data['fromTS']);
        }
        if(array_key_exists('toTS', $data)) {
            V::key('toTS', V::intVal())->check($data);
            $result->toTS = intval($data['toTS']);
        }
        if(array_key_exists('id', $data)) {
            V::key('id', V::intType())->check($data);
            $result->list = [$data['id']];
        } elseif(array_key_exists('list', $data)) {
            V::key('list', V::arrayVal()->each(V::intVal()))->check($data);
            $result->list = array_map('intval', $data['list']);
        } elseif(array_key_exists('ids', $data)) {
            // FIXME same as 'list', for backwards compatibility with frontend (event bulk updates and deletion)
            V::key('ids', V::arrayType()->each(V::intVal()))->check($data);
            $result->list = array_map('intval', $data['ids']);
        }
        return $result;
    }

    /**
     * Decodes and validates raw submitted event data.
     * The data array is expected to be formatted as follows:
     *  {
     *    "events": <events|base64>
     *  }
     *
     * The base64 encoded events data is expected to be another JSON string formatted as follows:
     *  [{
     *    "timestamp": <timestamp>,
     *    "service": <service>,
     *    "source": <source>,
     *    "summary": <summary>,
     *    "details": [{
     *      "timestamp": <timestamp>|null,
     *      "type": <type>,
     *      "data": <data>
     *    }, ...],
     *    "packets": [{
     *      "timestamp": <timestamp>,
     *      "protocol": <protocol>,
     *      "port": <port>,
     *      "headers": [{
     *        <field>: <value>
     *      }, ...],
     *      "payload": <payload|base64>
     *    }, ...}
     *  }, ...]
     *
     * @param array $data
     * @return array
     * @throws BadRequestException
     */
    private function validateEvents(array $data): array {
        // Basic attribute validation
        V::key('events', V::stringType())->check($data);
        // Decode events data
        try {
            $eventsData = json_decode(base64_decode($data['events']), true);
        } catch(\Exception $e) {
            throw new BadRequestException();
        }
        // Data segment validation
        V::arrayVal()
            ->each(V::arrayType()
                ->key('timestamp', V::intVal())
                ->key('details', V::arrayVal()->each(
                    V::arrayType()
                        ->key('timestamp', V::intVal())
                        ->key('type', V::intVal()->between(0, 1))
                        ->key('data', V::stringType())))
                ->key('packets', V::arrayVal()->each(
                    V::arrayType()
                        ->key('timestamp', V::intVal())
                        ->key('protocol', V::intVal()->between(0, 2))
                        ->key('port', V::intVal()->between(0, 65535))
                        ->key('payload', V::optional(V::stringType()))
                        ->key('headers', V::arrayVal())))
                ->key('service', V::intVal())
                ->key('source', V::stringType())
                ->key('summary', V::stringType()))
            ->check($eventsData);
        return $eventsData;
    }
}
