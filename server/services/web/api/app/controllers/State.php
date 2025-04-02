<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\services\DivisionsService;
use HoneySens\app\services\dto\EventFilterConditions;
use HoneySens\app\services\EventFiltersService;
use HoneySens\app\services\EventsService;
use HoneySens\app\services\PlatformsService;
use HoneySens\app\services\SensorServicesService;
use HoneySens\app\services\SensorsService;
use HoneySens\app\services\SettingsService;
use HoneySens\app\services\SystemService;
use HoneySens\app\services\TasksService;
use HoneySens\app\services\UsersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class State extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('', [State::class, 'getState']);
    }

    /**
     * Returns an array containing full current application state information (e.g. all entities)
     * that is accessible for the currently logged-in user.
     */
    public function getState(Request               $request,
                             Response              $response,
                             EntityManager         $em,
                             DivisionsService      $divisionsService,
                             EventFiltersService   $eventFiltersService,
                             EventsService         $eventsService,
                             PlatformsService      $platformsService,
                             SensorsService        $sensorsService,
                             SensorServicesService $sensorServicesService,
                             SettingsService       $settingsService,
                             SystemService         $systemService,
                             TasksService $tasksService,
                             UsersService $usersService): Response {
        $this->assureAllowed('get');
        $queryParams = $request->getQueryParams();
        $ts = $queryParams['ts'] ?? null;
        $lastEventId = $queryParams['last_id'] ?? null;
        V::optional(V::intVal())->check($ts);
        V::optional(V::oneOf(V::intVal(), V::equals('null')))->check($lastEventId);
        $now = new \DateTime();
        $sessionUser = null;
        try {
            $sessionUser = $this->getSessionUser();
            $state = $this->getEntities(
                $em,
                $platformsService,
                $sensorsService,
                $usersService,
                $divisionsService,
                $settingsService,
                $eventFiltersService,
                $sensorServicesService,
                $tasksService,
                $sessionUser,
                $ts);
        } catch(ForbiddenException) {
            // In case no user is logged in
            $state = array();
        }
        if($ts === null) {
            $state['new_events'] = array();
            $state['system'] = $systemService->getSystemInfo($this->getServerCert());
            $state['user'] = $_SESSION['user'];
        } else {
            // Return incremental state
            $events = array();
            if($lastEventId && $sessionUser !== null) {
                // Return new events since the provided last event ID
                $filterConditions = Events::validateEventFilterConditions($sessionUser, $queryParams);
                $filterConditions->lastID = intval($lastEventId);
                $events = $eventsService->getEvents($sessionUser, $filterConditions)['items'];
            }
            $state['new_events'] = $events;
        }
        if($sessionUser !== null) {
            // The lastEventID is only returned if a user is currently logged in
            $lastEventFilter = new EventFilterConditions();
            $lastEventFilter->user = $sessionUser->role !== UserRole::ADMIN ? $this->getSessionUser() : null;
            $lastEventFilter->sortBy = 'id';
            $lastEventFilter->sortOrder = 'desc';
            $lastEvents = $eventsService->getEvents($this->getSessionUser(), $lastEventFilter, perPage: 1)['items'];
            $state['lastEventID'] = count($lastEvents) > 0 ? $lastEvents[0]['id'] : null;
        } else $state['lastEventID'] = null;
        $state['timestamp'] = $now->format('U');
        $response->getBody()->write(json_encode($state));
        return $response;
    }

    /**
     * Calculates an array of entities that have been changed since the given timestamp.
     */
    private function getEntities(EntityManager $em,
                                 PlatformsService $platformsService,
                                 SensorsService $sensorsService,
                                 UsersService $usersService,
                                 DivisionsService $divisionsService,
                                 SettingsService $settingsService,
                                 EventFiltersService $eventFiltersService,
                                 SensorServicesService $sensorServicesService,
                                 TasksService $tasksService,
                                 User $sessionUser,
                                 ?int $ts = null): array {
        $result = array();
        if($ts === null) {
            $lastUpdates = [
                0 => ['name' => 'platforms'],
                1 => ['name' => 'sensors'],
                2 => ['name' => 'users'],
                3 => ['name' => 'divisions'],
                4 => ['name' => 'contacts'],
                5 => ['name' => 'settings'],
                6 => ['name' => 'event_filters'],
                7 => ['name' => 'services'],
                8 => ['name' => 'tasks']
            ];
        } else {
            $timestamp = new \DateTime('@' . $ts);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('table_name', 'name');
            $query = $em->createNativeQuery('SELECT table_name FROM last_updates WHERE timestamp >= ?', $rsm);
            $query->setParameter(1, $timestamp, "datetime");
            $lastUpdates = $query->getResult();
        }
        foreach($lastUpdates as $table) {
            $result[$table['name']] = [];
            switch($table['name']) {
                case 'platforms':
                    try {
                        $this->assureAllowed('get', 'platforms');
                        $result[$table['name']] = $platformsService->getPlatforms();
                    } catch (\Exception $e) {}
                    break;
                case 'sensors':
                    try {
                        $this->assureAllowed('get', 'sensors');
                        $result[$table['name']] = $sensorsService->getSensors($sessionUser);
                    } catch(\Exception $e) {}
                    break;
                case 'users':
                    try {
                        $this->assureAllowed('get', 'users');
                        $result[$table['name']] = $usersService->getUsers($sessionUser);
                    } catch(\Exception $e) {}
                    break;
                case 'divisions':
                    try {
                        $this->assureAllowed('get', 'divisions');
                        $result[$table['name']] = $divisionsService->getDivisions($sessionUser);
                    } catch(\Exception $e) {}
                    break;
                case 'contacts':
                    try {
                        $this->assureAllowed('get', 'contacts');
                        $result[$table['name']] = $divisionsService->getContact($sessionUser);
                    } catch(\Exception $e) {}
                    break;
                case 'settings':
                    try {
                        $this->assureAllowed('get', 'settings');
                        $result[$table['name']] = $settingsService->getSettings($sessionUser->role === UserRole::ADMIN);
                    } catch(\Exception $e) {}
                    break;
                case 'event_filters':
                    try {
                        $this->assureAllowed('get', 'eventfilters');
                        $result[$table['name']] = $eventFiltersService->getEventFilters($sessionUser);
                    } catch(\Exception $e) {}
                    break;
                case 'services':
                    try {
                        $this->assureAllowed('get', 'services');
                        $result[$table['name']] = $sensorServicesService->getServices();
                    } catch(\Exception $e) {}
                    break;
                case 'tasks':
                    try {
                        $this->assureAllowed('get', 'tasks');
                        $result[$table['name']] = $tasksService->getTasks($sessionUser);
                    } catch(\Exception $e) {}
                    break;
            }
        }
        return $result;
    }
}
