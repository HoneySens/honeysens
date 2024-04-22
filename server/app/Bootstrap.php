<?php
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\LazyOpenStream;
use HoneySens\app\adapters\JsonBodyParserMiddleware;
use HoneySens\app\adapters\SessionMiddleware;
use HoneySens\app\adapters\SetupCheckMiddleware;
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
use HoneySens\app\models\EntityUpdateSubscriber;
use HoneySens\app\models\exceptions;
use HoneySens\app\models\ServiceManager;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use \Respect\Validation\Exceptions\ValidationException;
use \Slim\Factory\AppFactory;
use \Slim\Routing\RouteCollectorProxy;
use \Symfony\Component\Cache\Adapter\PhpFilesAdapter;

// Global paths
define('BASE_PATH', realpath(sprintf('%s/../', dirname(__FILE__))));
define('APPLICATION_PATH', sprintf('%s/app', BASE_PATH));
define('DATA_PATH', sprintf('%s/data', BASE_PATH));
set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/vendor'), get_include_path())));

function initSlim($appConfig, $em) {
    $debug = $appConfig->getBoolean('server', 'debug');
    $app = AppFactory::create();
    $app->addRoutingMiddleware();
    $app->add(new JsonBodyParserMiddleware());
    $app->add(new SessionMiddleware());
    $app->add(new SetupCheckMiddleware($app, $em));
    $errorMiddleware = $app->addErrorMiddleware($debug, true, true);
    if(!$debug) {
        $errorMiddleware->setDefaultErrorHandler(function (Request $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails, ?LoggerInterface $logger = null) use ($app) {
            switch (true) {
                case $exception instanceof exceptions\ForbiddenException:
                    $status = 403;
                    $result = array('code' => $exception->getCode());
                    break;
                case $exception instanceof exceptions\NotFoundException:
                    $status = 404;
                    $result = array('code' => $exception->getCode());
                    break;
                case $exception instanceof exceptions\BadRequestException:
                case $exception instanceof ValidationException:
                    $status = 400;
                    $result = array('code' => $exception->getCode());
                    break;
                default:
                    $status = 500;
                    $result = array('error' => $exception->getMessage());
                    break;
            }
            $response = $app->getResponseFactory()->createResponse()->withStatus($status);
            $response->getBody()->write(json_encode($result));
            return $response;
        });
    }
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

function initDatabase() {
    $config = new Configuration();
    $config->setMetadataCache(new PhpFilesAdapter('doctrine_metadata'));
    $config->setQueryCache(new PhpFilesAdapter('doctrine_queries'));
    $config->setResultCache(new PhpFilesAdapter('doctrine_results'));
    $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(APPLICATION_PATH . '/models/entities', false));
    $config->setProxyDir(APPLICATION_PATH . '/../cache');
    $config->setAutoGenerateProxyClasses(true);
    $config->setProxyNamespace('HoneySens\Cache\Proxies');
    $config->addCustomDatetimeFunction('DAY', '\DoctrineExtensions\Query\Mysql\Day');
    $config->addCustomDatetimeFunction('MONTH', '\DoctrineExtensions\Query\Mysql\Month');
    $config->addCustomDatetimeFunction('YEAR', '\DoctrineExtensions\Query\Mysql\Year');
    $connectionParams = array(
        'driver' => 'pdo_mysql',
        'host' => getenv('HS_DB_HOST'),
        'port' => getenv('HS_DB_PORT'),
        'user' => getenv('HS_DB_USER'),
        'password' => getenv('HS_DB_PASSWORD'),
        'dbname' => getenv('HS_DB_NAME')
    );
    $em = EntityManager::create($connectionParams, $config);
    $em->getEventManager()->addEventSubscriber(new EntityUpdateSubscriber());
    return $em;
}

/**
 * URL route definitions
 *
 * @param $app Slim\Slim
 * @param $em Doctrine\ORM\EntityManager
 * @param $services ServiceManager
 * @param $config ConfigParser
 */
function initRoutes($app, $em, $services, $config) {
    // Deliver the web application
    $app->get('/', function(Request $request, Response $response) use ($app, $em, $services, $config) {
        $template = new LazyOpenStream(APPLICATION_PATH . '/templates/index.html', 'r');
        return $response->withBody($template);
    });

    // Register API routes
    $app->group('/api', function(RouteCollectorProxy $api) use ($em, $services, $config) {
        $api->group('/certs', function(RouteCollectorProxy $certs) use ($em, $services, $config) {
            Certs::registerRoutes($certs, $em, $services, $config);
        });
        $api->group('/contacts', function(RouteCollectorProxy $contacts) use ($em, $services, $config) {
            Contacts::registerRoutes($contacts, $em, $services, $config);
        });
        $api->group('/divisions', function(RouteCollectorProxy $divisions) use ($em, $services, $config) {
            Divisions::registerRoutes($divisions, $em, $services, $config);
        });
        $api->group('/eventdetails', function(RouteCollectorProxy $evDetails) use ($em, $services, $config) {
            Eventdetails::registerRoutes($evDetails, $em, $services, $config);
        });
        $api->group('/eventfilters', function(RouteCollectorProxy $evFilters) use ($em, $services, $config) {
            Eventfilters::registerRoutes($evFilters, $em, $services, $config);
        });
        $api->group('/events', function(RouteCollectorProxy $events) use ($em, $services, $config) {
            Events::registerRoutes($events, $em, $services, $config);
        });
        $api->group('/logs', function(RouteCollectorProxy $logs) use ($em, $services, $config) {
            Logs::registerRoutes($logs, $em, $services, $config);
        });
        $api->group('/platforms', function(RouteCollectorProxy $platforms) use ($em, $services, $config) {
            Platforms::registerRoutes($platforms, $em, $services, $config);
        });
        $api->group('/sensors', function(RouteCollectorProxy $sensors) use ($em, $services, $config) {
            Sensors::registerRoutes($sensors, $em, $services, $config);
        });
        $api->group('/services', function(RouteCollectorProxy $apiServices) use ($em, $services, $config) {
            Services::registerRoutes($apiServices, $em, $services, $config);
        });
        $api->group('/sessions', function(RouteCollectorProxy $sessions) use ($em, $services, $config) {
            Sessions::registerRoutes($sessions, $em, $services, $config);
        });
        $api->group('/settings', function(RouteCollectorProxy $settings) use ($em, $services, $config) {
            Settings::registerRoutes($settings, $em, $services, $config);
        });
        $api->group('/state', function(RouteCollectorProxy $state) use ($em, $services, $config) {
            State::registerRoutes($state, $em, $services, $config);
        });
        $api->group('/stats', function(RouteCollectorProxy $stats) use ($em, $services, $config) {
            Stats::registerRoutes($stats, $em, $services, $config);
        });
        $api->group('/system', function(RouteCollectorProxy $system) use ($em, $services, $config) {
            System::registerRoutes($system, $em, $services, $config);
        });
        $api->group('/tasks', function(RouteCollectorProxy $tasks) use ($em, $services, $config) {
            Tasks::registerRoutes($tasks ,$em, $services, $config);
        });
        $api->group('/templates', function(RouteCollectorProxy $templates) use ($em, $services, $config) {
            Templates::registerRoutes($templates ,$em, $services, $config);
        });
        $api->group('/users', function(RouteCollectorProxy $users) use ($em, $services, $config) {
            Users::registerRoutes($users, $em, $services, $config);
        });
    });
}

/**
 * Primary entry point, initializes all components and routes, then runs the route dispatcher.
 * This method blocks until the request has been served.
 */
function launch() {
    initClassLoading();
    $config = initConfig();
    $em = initDatabase();
    $app = initSlim($config, $em);
    $services = new ServiceManager($config, $em);
    initRoutes($app, $em, $services, $config);
    $app->run();
}
