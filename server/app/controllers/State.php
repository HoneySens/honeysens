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
            if($ts == null) {
                // Return full state
                $state = $controller->get($userID);
            } else {
                // Return incremental state
                if($lastEventId) {
                    $eventsController = new Events($em, $services, $config);
                    $events = $eventsController->get(array_merge($stateParams, array('lastID' => $lastEventId)));
                } else {
                    $events = (new Events($em, $services, $config))->get($stateParams);
                }
                $updateService = $services->get(ServiceManager::SERVICE_ENTITY_UPDATE);
                $state = $updateService->getUpdatedEntities($em, $services, $config, $ts, $stateParams);
                $state['events'] = $events;
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

        // If an update is required, prioritize that. We can't guarantee that getting all the other data will be successful otherwise.
        try { $system = (new System($em, $this->getServiceManager(), $config))->get(); } catch(\Exception $e) { $system = array(); }
        if($system['update']) {
            return array(
                'user' => $_SESSION['user'],
                'sensors' => array(),
                'events' => array(),
                'event_filters' => array(),
                'users' => array(),
                'divisions' => array(),
                'contacts' => array(),
                'services' => array(),
                'platform' => array(),
                'settings' => array(),
                'system' => $system,
                'stats' => array()
            );
        }

        try { $sensors = (new Sensors($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $sensors = array(); }
        try { $events = (new Events($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $events = array(); }
        try { $event_filters = (new Eventfilters($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $event_filters = array(); }
        try { $users = (new Users($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $users = array(); }
        try { $divisions = (new Divisions($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $divisions = array(); }
        try { $contacts = (new Contacts($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $contacts = array(); }
        try { $services = (new Services($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $services = array(); }
        try { $platforms = (new Platforms($em, $this->getServiceManager(), $config))->get(array()); } catch(\Exception $e) { $platforms = array(); }
        try { $settings = (new Settings($em, $this->getServiceManager(), $config))->get(); } catch(\Exception $e) { $settings = array(); }
        try { $stats = (new Stats($em, $this->getServiceManager(), $config))->get(array('userID' => $userID)); } catch(\Exception $e) { $stats = array(); }

        return array(
            'user' => $_SESSION['user'],
            'sensors' => $sensors,
            'events' => $events,
            'event_filters' => $event_filters,
            'users' => $users,
            'divisions' => $divisions,
            'contacts' => $contacts,
            'services' => $services,
            'platforms' => $platforms,
            'settings' => $settings,
            'system' => $system,
            'stats' => $stats
        );
    }
}
