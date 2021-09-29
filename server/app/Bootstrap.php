<?php

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use HoneySens\app\controllers\Certs;
use HoneySens\app\controllers\Contacts;
use HoneySens\app\controllers\Divisions;
use HoneySens\app\controllers\Eventdetails;
use HoneySens\app\controllers\Eventfilters;
use HoneySens\app\controllers\Events;
use HoneySens\app\controllers\Logs;
use HoneySens\app\controllers\Platforms;
use HoneySens\app\controllers\Sensors;
use HoneySens\app\controllers\Services;
use HoneySens\app\controllers\Sessions;
use HoneySens\app\controllers\Settings;
use HoneySens\app\controllers\State;
use HoneySens\app\controllers\Stats;
use HoneySens\app\controllers\System;
use HoneySens\app\controllers\Tasks;
use HoneySens\app\controllers\Templates;
use HoneySens\app\controllers\Users;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\EntityUpdateSubscriber;
use HoneySens\app\models\exceptions;
use HoneySens\app\models\ServiceManager;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use \Respect\Validation\Exceptions\ValidationException;
use Slim\Route;
use Slim\Slim;

// Global paths
define('BASE_PATH', realpath(sprintf('%s/../', dirname(__FILE__))));
define('APPLICATION_PATH', sprintf('%s/app', BASE_PATH));
define('DATA_PATH', sprintf('%s/data', BASE_PATH));
set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/vendor'), get_include_path())));

function initSlim($appConfig) {
    $debug = $appConfig->getBoolean('server', 'debug');
    $app = new Slim(array('templates.path' => APPLICATION_PATH . '/templates', 'debug' => $debug));
    // Set global error handler that translates exceptions into HTTP status codes
    $app->error(function(\Exception $e) use ($app) {
        switch(true) {
            case $e instanceof exceptions\ForbiddenException:
                $app->response->setStatus(403);
                echo json_encode(array('code' => $e->getCode()));
                break;
            case $e instanceof exceptions\NotFoundException:
                $app->response->setStatus(404);
                echo json_encode(array('code' => $e->getCode()));
                break;
            case $e instanceof exceptions\BadRequestException:
            case $e instanceof ValidationException:
                $app->response->setStatus(400);
                echo json_encode(array('code' => $e->getCode()));
                break;
            default:
                $app->response->setStatus(500);
                echo json_encode(array('error' => $e->getMessage()));
                break;
        }
    });
    // Global route conditions
    Route::setDefaultConditions(array('id' => '\d+'));
    return $app;
}

function initClassLoading() {
    require_once('vendor/autoload.php');
}

function initConfig() {
    $config = new ConfigParser();
    $config->read(APPLICATION_PATH . '/../data/config.cfg');
    return $config;
}

function initDoctrine() {
    $config = new Configuration();
    $cache = new ArrayCache();
    $config->setMetadataCacheImpl($cache);
    $config->setQueryCacheImpl($cache);
    $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(APPLICATION_PATH . '/models/entities'));
    $config->setProxyDir(APPLICATION_PATH . '/../cache');
    $config->setAutoGenerateProxyClasses(true);
    $config->setProxyNamespace('HoneySens\Cache\Proxies');
    $config->addCustomDatetimeFunction('DAY', '\DoctrineExtensions\Query\Mysql\Day');
    $config->addCustomDatetimeFunction('MONTH', '\DoctrineExtensions\Query\Mysql\Month');
    $config->addCustomDatetimeFunction('YEAR', '\DoctrineExtensions\Query\Mysql\Year');
    $connectionParams = array(
        'driver' => 'pdo_mysql',
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME')
    );
    return EntityManager::create($connectionParams, $config);
}

function initDBSchema(&$messages, $em) {
    $systemController = new System($em, null, null);
    $systemController->initDBSchema($messages, $em, true);
}

function initDBEventManager($em) {
    $em->getEventManager()->addEventSubscriber(new EntityUpdateSubscriber());
}

function initServiceManager($config, $em) {
    return new ServiceManager($config, $em);
}

/**
 * URL route definitions
 *
 * @param $app Slim\Slim
 * @param $em Doctrine\ORM\EntityManager
 * @param $services ServiceManager
 * @param $config ConfigParser
 * @param $messages array of events that happened during initialization of the form array( array( 'severity' => 'info|warn', 'msg' => $msg ), ... )
 */
function initRoutes($app, $em, $services, $config, $messages) {
    // Deliver the web application
    $app->get('/', function() use ($app, $em, $services, $config, $messages) {
        // Render system messages encountered during initialization
        if(count($messages) > 0) {
            $infoMsg = '';
            $warnMsg = '';
            foreach($messages as $message) {
                if($message['severity'] == 'info') $infoMsg .= $message['msg'] . '<br />';
                if($message['severity'] == 'warn') $warnMsg .= $message['msg'] . '<br />';
            }
            if($infoMsg) $app->flashNow('info', $infoMsg);
            if($warnMsg) $app->flashNow('warn', $warnMsg);
        }
        $app->render('layout.php');
    });

    // Initialize API
    Certs::registerRoutes($app, $em, $services, $config, $messages);
    Contacts::registerRoutes($app, $em, $services, $config, $messages);
    Divisions::registerRoutes($app, $em, $services, $config, $messages);
    Eventdetails::registerRoutes($app, $em, $services, $config, $messages);
    Eventfilters::registerRoutes($app, $em, $services, $config, $messages);
    Events::registerRoutes($app, $em, $services, $config, $messages);
    Logs::registerRoutes($app, $em, $services, $config, $messages);
    Platforms::registerRoutes($app, $em, $services, $config, $messages);
    Sensors::registerRoutes($app, $em, $services, $config, $messages);
    Services::registerRoutes($app, $em, $services, $config, $messages);
    Sessions::registerRoutes($app, $em, $services, $config, $messages);
    Settings::registerRoutes($app, $em, $services, $config, $messages);
    State::registerRoutes($app, $em, $services, $config, $messages);
    Stats::registerRoutes($app, $em, $services, $config, $messages);
    System::registerRoutes($app, $em, $services, $config, $messages);
    Tasks::registerRoutes($app ,$em, $services, $config, $messages);
    Templates::registerRoutes($app ,$em, $services, $config, $messages);
    Users::registerRoutes($app, $em, $services, $config, $messages);
}

function initSession($app) {
    session_cache_limiter(false);
    session_start();
    if(isset($_SESSION['last_activity'])) {
        // Handle session activity timeout
        if(time() - $_SESSION['last_activity'] > $_SESSION['timeout']) {
            session_unset();
            session_destroy();
            // 403 forbidden for API requests, '/' will be rendered regularly
            if(strpos($app->request->getPathInfo(), '/api/') === 0) {
                http_response_code(403);
                exit();
            }
        } else $_SESSION['last_activity'] = time();
    }
    if(!isset($_SESSION['authenticated']) || !isset($_SESSION['user'])) {
        $guestUser = new User();
        $guestUser->setRole(User::ROLE_GUEST);
        $_SESSION['authenticated'] = false;
        $_SESSION['user'] = $guestUser->getState();
    }
}
