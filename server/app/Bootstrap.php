<?php
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use \DI\Bridge\Slim\Bridge;
use \DI\Container;
use GuzzleHttp\Psr7\LazyOpenStream;
use HoneySens\app\middleware\JsonBodyParserMiddleware;
use HoneySens\app\middleware\SessionMiddleware;
use HoneySens\app\middleware\SetupCheckMiddleware;
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
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use \Respect\Validation\Exceptions\ValidationException;
use \Slim\Routing\RouteCollectorProxy;
use \Symfony\Component\Cache\Adapter\PhpFilesAdapter;

// Global paths
define('BASE_PATH', realpath(sprintf('%s/../', dirname(__FILE__))));
define('APPLICATION_PATH', sprintf('%s/app', BASE_PATH));
define('DATA_PATH', sprintf('%s/data', BASE_PATH));
set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/vendor'), get_include_path())));

function initSlim(ContainerInterface $container) {
    $debug = $container->get('NoiseLabs\ToolKit\ConfigParser\ConfigParser')->getBoolean('server', 'debug');
    $em = $container->get('Doctrine\ORM\EntityManager');
    $app = Bridge::create($container);
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

function createDependencyContainer(EntityManager $em) {
    return new Container([
        'NoiseLabs\ToolKit\ConfigParser\ConfigParser' => function() {
            $config = new ConfigParser();
            $config->read(APPLICATION_PATH . '/../data/config.cfg');
            return $config;
        },
        'Doctrine\ORM\EntityManager' => function() use ($em) {
            return $em;
        }
    ]);
}

/**
 * URL route definitions
 *
 * @param $app Slim\Slim
 */
function initRoutes($app) {
    // Deliver the web application
    $app->get('/', function(Request $request, Response $response) {
        $template = new LazyOpenStream(APPLICATION_PATH . '/templates/index.html', 'r');
        return $response->withBody($template);
    });

    // Register API routes
    $app->group('/api', function(RouteCollectorProxy $api) {
        $api->group('/certs', function(RouteCollectorProxy $certs) {
            Certs::registerRoutes($certs);
        });
        $api->group('/contacts', function(RouteCollectorProxy $contacts) {
            Contacts::registerRoutes($contacts);
        });
        $api->group('/divisions', function(RouteCollectorProxy $divisions) {
            Divisions::registerRoutes($divisions);
        });
        $api->group('/eventdetails', function(RouteCollectorProxy $evDetails) {
            Eventdetails::registerRoutes($evDetails);
        });
        $api->group('/eventfilters', function(RouteCollectorProxy $evFilters) {
            Eventfilters::registerRoutes($evFilters);
        });
        $api->group('/events', function(RouteCollectorProxy $events) {
            Events::registerRoutes($events);
        });
        $api->group('/logs', function(RouteCollectorProxy $logs) {
            Logs::registerRoutes($logs);
        });
        $api->group('/platforms', function(RouteCollectorProxy $platforms) {
            Platforms::registerRoutes($platforms);
        });
        $api->group('/sensors', function(RouteCollectorProxy $sensors) {
            Sensors::registerRoutes($sensors);
        });
        $api->group('/services', function(RouteCollectorProxy $apiServices) {
            Services::registerRoutes($apiServices);
        });
        $api->group('/sessions', function(RouteCollectorProxy $sessions) {
            Sessions::registerRoutes($sessions);
        });
        $api->group('/settings', function(RouteCollectorProxy $settings) {
            Settings::registerRoutes($settings);
        });
        $api->group('/state', function(RouteCollectorProxy $state) {
            State::registerRoutes($state);
        });
        $api->group('/stats', function(RouteCollectorProxy $stats) {
            Stats::registerRoutes($stats);
        });
        $api->group('/system', function(RouteCollectorProxy $system) {
            System::registerRoutes($system);
        });
        $api->group('/tasks', function(RouteCollectorProxy $tasks) {
            Tasks::registerRoutes($tasks);
        });
        $api->group('/templates', function(RouteCollectorProxy $templates) {
            Templates::registerRoutes($templates);
        });
        $api->group('/users', function(RouteCollectorProxy $users) {
            Users::registerRoutes($users);
        });
    });
}

/**
 * Primary entry point, initializes all components and routes, then runs the route dispatcher.
 * This method blocks until the request has been served.
 */
function launch() {
    require_once('vendor/autoload.php');
    $em = initDatabase();
    $dependencyContainer = createDependencyContainer($em);
    $app = initSlim($dependencyContainer);
    initRoutes($app);
    $app->run();
}
