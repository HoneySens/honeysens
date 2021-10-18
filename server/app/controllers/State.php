<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

class State extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        // Returns an array containing full current application state information (e.g. all entities)
        // that is accessible for the given user.
        $app->get('/api/state', function() use ($app, $em, $services, $config, $messages) {
            $controller = new State($em, $services, $config);
            // Set $userID to null for global admin users to avoid user-specific filtering
            $userID = $controller->getSessionUserID();
            $ts = $app->request()->get('ts');
            $lastEventId = $app->request()->get('last_id');
            $stateParams = $app->request()->get();
            $stateParams['userID'] = $userID;
            V::optional(V::intVal())->check($ts);
            V::optional(V::oneOf(V::intVal(), V::equals('null')))->check($lastEventId);
            $now = new \DateTime();
            $eventsController = new Events($em, $services, $config);
            if($ts == null) {
                // Return full state
                $state = $controller->get($userID);
            } else {
                // Return incremental state
                $events = array();
                if($lastEventId) {
                    // Return new events since the provided last event ID
                    $events = $eventsController->get(array_merge($stateParams, array('lastID' => $lastEventId)))['items'];
                }
                $updateService = $services->get(ServiceManager::SERVICE_ENTITY_UPDATE);
                $state = $updateService->getUpdatedEntities($em, $services, $config, $ts, $stateParams);
                $state['new_events'] = $events;
            }
            try {
                // The lastEventID is only returned if a user is logged in, otherwise this will throw a ForbiddenException
                $state['lastEventID'] = $eventsController->get(array(
                    'userID' => $userID,
                    'sort_by' => 'id',
                    'order' => 'desc',
                    'page' => 0,
                    'per_page' => 1
                ))['items'][0]['id'];
            } catch(\Exception $e) {
                $state['lastEventID'] = null;
            }
            $state['timestamp'] = $now->format('U');
            echo json_encode($state);
        });
    }

    // TODO add permission resource
    public function get($userID) {
        $this->assureAllowed('get');
        $em = $this->getEntityManager();
        $config = $this->getConfig();

        try { $sensors = (new Sensors($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $sensors = array(); }
        try { $event_filters = (new Eventfilters($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $event_filters = array(); }
        try { $users = (new Users($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $users = array(); }
        try { $divisions = (new Divisions($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $divisions = array(); }
        try { $contacts = (new Contacts($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $contacts = array(); }
        try { $services = (new Services($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $services = array(); }
        try { $platforms = (new Platforms($em, $this->getServiceManager(), $config))->get(array()); } catch(\Exception $e) { $platforms = array(); }
        try { $settings = (new Settings($em, $this->getServiceManager(), $config))->get(); } catch(\Exception $e) { $settings = array(); }
        try { $stats = (new Stats($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $stats = array(); }
        try { $tasks = (new Tasks($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $tasks = array(); }
        try { $system = (new System($em, $this->getServiceManager(), $config))->get(); } catch(\Exception $e) { $system = array(); }

        return array(
            'user' => $_SESSION['user'],
            'sensors' => $sensors,
            'new_events' => array(),
            'event_filters' => $event_filters,
            'users' => $users,
            'divisions' => $divisions,
            'contacts' => $contacts,
            'services' => $services,
            'platforms' => $platforms,
            'settings' => $settings,
            'system' => $system,
            'stats' => $stats,
            'tasks' => $tasks
        );
    }
}
