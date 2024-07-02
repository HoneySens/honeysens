<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use HoneySens\app\services\ContactsService;
use HoneySens\app\services\DivisionsService;
use HoneySens\app\services\dto\EventFilterConditions;
use HoneySens\app\services\EventFiltersService;
use HoneySens\app\services\EventsService;
use HoneySens\app\services\PlatformsService;
use HoneySens\app\services\SensorServicesService;
use HoneySens\app\services\SensorsService;
use HoneySens\app\services\SettingsService;
use HoneySens\app\services\StatsService;
use HoneySens\app\services\SystemService;
use HoneySens\app\services\TasksService;
use HoneySens\app\services\UsersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class State extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [State::class, 'get']);
    }

    /**
     * Returns an array containing full current application state information (e.g. all entities)
     *  that is accessible for the given user.
     */
    public function get(Request $request,
                        Response $response,
                        EntityManager $em,
                        DivisionsService $divisionsService,
                        EventFiltersService $eventFiltersService,
                        EventsService $eventsService,
                        PlatformsService $platformsService,
                        SensorsService $sensorsService,
                        SensorServicesService $sensorServicesService,
                        SettingsService $settingsService,
                        StatsService $statsService,
                        SystemService $systemService,
                        TasksService $tasksService,
                        UsersService $usersService) {
        $this->assureAllowed('get');
        // Set $userID to null for admin users to avoid user-specific filtering
        $userID = $this->getSessionUserID();
        $queryParams = $request->getQueryParams();
        $ts = $queryParams['ts'] ?? null;
        $lastEventId = $queryParams['last_id'] ?? null;
        $stateParams = $queryParams;
        $stateParams['userID'] = $userID;
        V::optional(V::intVal())->check($ts);
        V::optional(V::oneOf(V::intVal(), V::equals('null')))->check($lastEventId);
        $now = new \DateTime();
        $state = $this->getEntities(
            $em,
            $platformsService,
            $sensorsService,
            $usersService,
            $divisionsService,
            $settingsService,
            $eventFiltersService,
            $sensorServicesService,
            $statsService,
            $tasksService,
            $ts,
            $stateParams);
        if($ts === null) {
            $state['new_events'] = array();
            $state['system'] = $systemService->getSystemInfo();
            $state['user'] = $_SESSION['user'];
        } else {
            // Return incremental state
            $events = array();
            if($lastEventId) {
                // Return new events since the provided last event ID
                $filterConditions = Events::validateEventFilterConditions($this->getSessionUser(), $queryParams);
                $filterConditions->lastID = intval($lastEventId);
                $events = $eventsService->get($this->getSessionUser(), $filterConditions)['items'];
            }
            $state['new_events'] = $events;
        }
        try {
            // The lastEventID is only returned if a user is logged in, otherwise this will throw a ForbiddenException
            $lastEventFilter = new EventFilterConditions();
            $lastEventFilter->user = $this->getSessionUser();
            $lastEventFilter->sortBy = 'id';
            $lastEventFilter->sortOrder = 'desc';
            $lastEvents = $eventsService->get($this->getSessionUser(), $lastEventFilter, perPage: 1)['items'];
            $state['lastEventID'] = count($lastEvents) > 0 ? $lastEvents[0]['id'] : null;
        } catch(\Exception $e) {
            $state['lastEventID'] = null;
        }
        $state['timestamp'] = $now->format('U');
        $response->getBody()->write(json_encode($state));
        return $response;
    }

    /**
     * Used to calculate an array of entities that have been changed since the given timestamp.
     *
     * @param $ts int UNIX timestamp
     * @param $attributes array An optional array of attributes that is passed on to all affected controller methods
     * @return array
     */
    private function getEntities(EntityManager $em,
                                 PlatformsService $platformsService,
                                 SensorsService $sensorsService,
                                 UsersService $usersService,
                                 DivisionsService $divisionsService,
                                 SettingsService $settingsService,
                                 EventFiltersService $eventFiltersService,
                                 SensorServicesService $sensorServicesService,
                                 StatsService $statsService,
                                 TasksService $tasksService,
                                 ?int $ts = null,
                                 array $attributes = array()) {
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
                8 => ['name' => 'stats'],
                9 => ['name' => 'tasks']
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
                        $result[$table['name']] = $platformsService->get();
                    } catch (\Exception $e) {}
                    break;
                case 'sensors':
                    try {
                        $this->assureAllowed('get', 'sensors');
                        $result[$table['name']] = $sensorsService->get($this->getSessionUser());
                    } catch(\Exception $e) {}
                    break;
                case 'users':
                    try {
                        $this->assureAllowed('get', 'users');
                        $result[$table['name']] = $usersService->get($attributes);
                    } catch(\Exception $e) {}
                    break;
                case 'divisions':
                    try {
                        $this->assureAllowed('get', 'divisions');
                        $result[$table['name']] = $divisionsService->get($this->getSessionUser());
                    } catch(\Exception $e) {}
                    break;
                case 'contacts':
                    try {
                        $this->assureAllowed('get', 'contacts');
                        $result[$table['name']] = $divisionsService->getContact($this->getSessionUser());
                    } catch(\Exception $e) {}
                    break;
                case 'settings':
                    try {
                        $this->assureAllowed('get', 'settings');
                        $result[$table['name']] = $settingsService->get($this->getSessionUserID());
                    } catch(\Exception $e) {}
                    break;
                case 'event_filters':
                    try {
                        $this->assureAllowed('get', 'eventfilters');
                        $result[$table['name']] = $eventFiltersService->get($this->getSessionUser());
                    } catch(\Exception $e) {}
                    break;
                case 'services':
                    try {
                        $this->assureAllowed('get', 'services');
                        $result[$table['name']] = $sensorServicesService->get($attributes);
                    } catch(\Exception $e) {}
                    break;
                case 'stats':
                    try {
                        if ($ts === null) {
                            $result[$table['name']] = $statsService->get(array(
                                'userID' => $attributes['userID']));
                        } else {
                            $result[$table['name']] = $statsService->get(array(
                                'userID' => $attributes['userID'],
                                'year' => $attributes['stats_year'],
                                'month' => $attributes['stats_month'],
                                'division' => $attributes['stats_division']));
                        }
                    } catch(\Exception $e) {}
                    break;
                case 'tasks':
                    try {
                        $this->assureAllowed('get', 'tasks');
                        $result[$table['name']] = $tasksService->get($attributes);
                    } catch(\Exception $e) {}
                    break;
            }
        }
        return $result;
    }
}
