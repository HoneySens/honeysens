<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\Tools\SchemaTool;
use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\ServiceManager;
use NoiseLabs\ToolKit\ConfigParser\Exception\NoOptionException;
use phpseclib\File\X509;
use Respect\Validation\Validator as V;

class System extends RESTResource {

    const VERSION = '18.12.01';
    const ERR_UNKNOWN = 0;
    const ERR_CONFIG_WRITE = 1;

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/triggerWeeklySummary', function() use ($app, $em, $services, $config, $messages) {
            // TODO Consider moving this to a beanstalk worker, add authentication
            $contactService = $services->get(ServiceManager::SERVICE_CONTACT);
            echo json_encode($contactService->sendWeeklySummary($config, $em));
        });

        $app->get('/api/system', function() use ($app, $em, $services, $config, $messages) {
            $controller = new System($em, $services, $config);
            $systemData = $controller->get();
            echo json_encode($systemData);
        });

        $app->get('/api/system/identify', function() use ($app, $em, $services, $config, $messages) {
            // Predictable endpoint used to test the server connection (useful to figure out if a proxy actually works)
            echo 'HoneySens';
        });

        $app->delete('/api/system/events', function() use ($app, $em, $services, $config, $messages) {
            $controller = new System($em, $services, $config);
            try {
                $controller->removeAllEvents();
                echo json_encode([]);
            } catch(\Exception $e) {
                throw new BadRequestException();
            }
        });

        /*
         * TODO This does clear the platforms table and doesn't add them again, thus breaking the system
         * (amongst potential other issues).
         *
        $app->delete('/api/system/db', function() use ($app, $em, $services, $config, $messages) {
            $controller = new System($em, $services, $config);
            $messages = array();
            try {
                $controller->initDBSchema($messages, $em, false, true);
                echo json_encode(array('messages' => $messages));
            } catch(\Exception $e) {
                throw new BadRequestException();
            }
        });
        */

        $app->put('/api/system/db', function() use ($app, $em, $services, $config, $messages) {
            $controller = new System($em, $services, $config);
            $messages = array();
            $controller->updateDBSchema($messages, $em);
            echo json_encode(array('messages' => $messages));
        });

        $app->post('/api/system/install', function() use ($app, $em, $services, $config, $messages) {
            $controller = new System($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $installData = json_decode($request);
            $systemData = $controller->install($installData);
            echo json_encode($systemData);
        });

        $app->get('/api/system/update', function() use ($app, $em, $services, $config, $messages) {
            // Returns HTTP Status 403 if an update is running and 200 otherwise (e.g., if it was completed).
            // This can be used by clients to check when an update they started is finished.
            if(file_exists(realpath(APPLICATION_PATH . '/../data/UPDATE'))) throw new ForbiddenException();
            else json_encode([]);
        });

        $app->post('/api/system/update', function() use ($app, $em, $services, $config, $messages) {
            $controller = new System($em, $services, $config);
            $controller->update();
            echo json_encode([]);
        });
    }

    /**
     * Figures out if the installer hasn't been run on this installation yet.
     *
     * @param $config
     * @return bool
     */
    static function installRequired($config) {
        try {
            return $config->getBoolean('server', 'setup');
        } catch(NoOptionException $e) {
            // If the option doesn't exist, we run a configuration < 0.2.0 and therefore don't need to reinstall
            return false;
        }
    }

    /**
     * Figures out if execution of the updater is required.
     *
     * @param $config
     * @return bool
     */
    static function updateRequired($config) {
        try {
            $version = $config->get('server', 'config_version', null);
        } catch(NoOptionException $e) {
            return true;
        }
        return System::VERSION != $version;
    }

    /**
     * Returns data that is relevant for the setup and update processes
     */
    public function get() {
        $config = $this->getConfig();
        // Fetch TLS cert common name
        $certfile = $config['server']['certfile'];
        $commonName = null;
        $x509 = new X509();
        try {
            // Manually select the first cert of a potential chain (see https://github.com/phpseclib/phpseclib/issues/708)
            $certs = preg_split('#-+BEGIN CERTIFICATE-+#', file_get_contents($certfile));
            array_shift($certs); // Remove the first empty element
            $cert = $x509->loadX509(sprintf('%s%s', '-----BEGIN CERTIFICATE-----', array_shift($certs)));
            foreach($cert['tbsCertificate']['subject']['rdnSequence'] as $prim) {
                foreach($prim as $sec) {
                    if(array_key_exists('type', $sec) && $sec['type'] == 'id-at-commonName') {
                        if(array_key_exists('value', $sec) && is_array($sec['value'])) {
                            $commonName = $sec['value'][key($sec['value'])];
                        }
                    }
                }
            }
        } catch(\Exception $e) {
            $commonName = null;
        }
        return array(
            'version' => $this::VERSION,
            'cert_cn' => $commonName,
            'setup' => $this::installRequired($config),
            'update' => $this::updateRequired($config));
    }

    /**
     * Removes all events from the database
     *
     * @throws \Exception
     */
    public function removeAllEvents() {
        // QueryBuilder seems to ignore the cascade on delete specifications and fails with constraint checks,
        // if we just delete events here. As a workaround we will manually do the cascade stuff by deleting
        // referenced event details and packets first.
        $this->assureAllowed('delete', 'events');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete('HoneySens\app\models\entities\EventDetail', 'ed');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\EventPacket', 'ep');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\Event', 'e');
        $qb->getQuery()->execute();
    }

    /**
     * Checks existence of parts of the DB schema and triggers the setup process if necessary.
     *
     * @param &$messages List of notification messages that is passed on between bootstrap functions
     * @param $em Doctrine entity manager
     * @param $skipPermissionCheck Boolean that determines if the permission checks are skipped (useful to reset the DB during bootstrap)
     * @param $forceReset Boolean that forces the removal of all data, even if the DB is in a clean shape
     * @throws \Exception
     */
    function initDBSchema(&$messages, $em, $skipPermissionCheck=false, $forceReset=false) {
        // This can only be invoked from an admin session
        if(!$skipPermissionCheck && $_SESSION['user']['role'] != User::ROLE_ADMIN) {
            throw new ForbiddenException();
        }
        $schemaManager = $em->getConnection()->getSchemaManager();
        if($forceReset || !$schemaManager->tablesExist(array('users'))) {
            // TODO replace this simple setup process with a more sophisticated frontend setup script and query for user data (admin passwd etc.)
            $con = $em->getConnection();
            $schemaTool = new SchemaTool($em);
            $classes = $em->getMetadataFactory()->getAllMetadata();
            // Remove existing tables
            $schemaTool->dropSchema($classes);
            $con->query('DROP TABLE IF EXISTS `last_updates`');
            // Create schema
            $schemaTool->createSchema($classes);
            $this->addLastUpdatesTable($em);
            // Default admin user
            $admin = new User();
            $admin->setName('admin')->setPassword(sha1('admin'))->setEmail('root@localhost.com')->setRole($admin::ROLE_ADMIN);
            $em->persist($admin);
            $em->flush();
            // Remove old data files
            exec(realpath(APPLICATION_PATH . '/scripts/clear_data.py') . ' ' . escapeshellarg(realpath(APPLICATION_PATH . '/../data')) . ' 2>&1', $output);
            $messages[] = array('severity' => 'info', 'msg' => 'Die Datenbank wurde neu initialisiert.');
        }
    }

    function updateDBSchema(&$messages, $em) {
        // This can only be invoked from an admin session
        if($_SESSION['user']['role'] != User::ROLE_ADMIN) {
            throw new ForbiddenException();
        }
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new Schematool($em);
        $sqls = $schemaTool->getUpdateSchemaSql($classes);
        $messages[] = array('severity' => 'info', 'msg' => 'Datenbankschema: ' + count($sqls) + ' Updates');
        $schemaTool->updateSchema($classes);
        $this->addLastUpdatesTable($em);
    }

    /**
     * Performs the initial configuration of a newly installed system.
     * Expects an object with the following parameters:
     * {
     *   password: <admin password>,
     *   serverEndpoint: <server endpoint>,
     *   divisionName: <name of the initial division to create>
     * }
     *
     * @param $data
     * @return array
     * @throws ForbiddenException
     */
    function install($data) {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
        if(!$this::installRequired($config)) {
            throw new ForbiddenException();
        };
        // Validation
        V::objectType()
            ->attribute('password', V::stringType()->length(6, 255))
            ->attribute('serverEndpoint', V::stringType())
            ->attribute('divisionName', V::alnum()->length(1, 255))
            ->check($data);
        // Persistence
        $connection = $em->getConnection();
        $connection->prepare('INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("1", "bbb", "BeagleBone Black", "BeagleBone Black is a low-cost, community-supported development platform.", "bbb")')->execute();
        $admin = $em->getRepository('HoneySens\app\models\entities\User')->find(1);
        $admin->setPassword(sha1($data->password));
        $config->set('server', 'host', $data->serverEndpoint);
        $config->set('server', 'setup', 'false');
        try {
            $config->save();
        } catch (\ErrorException $e) {
            throw new BadRequestException($this::ERR_CONFIG_WRITE);
        }
        $division = new Division();
        $division->setName($data->divisionName);
        $admin->addToDivision($division);
        $em->persist($division);
        $em->flush();
        return array('cert_cn' => $data->serverEndpoint,
            'setup' => false,
            'update' => false);
    }

    function update() {
        // This can only be invoked from an admin session and if an update is actually necessary
        if($_SESSION['user']['role'] != User::ROLE_ADMIN || !$this::updateRequired($this->getConfig())) {
            throw new ForbiddenException();
        }
        $this->getServiceManager()->get(ServiceManager::SERVICE_BEANSTALK)->putUpdateJob();
    }

    private function addLastUpdatesTable($em) {
        // Add non-model table 'last_updates'
        $connection = $em->getConnection();
        $connection->prepare('CREATE TABLE last_updates(table_name VARCHAR(50) PRIMARY KEY, timestamp DATETIME)')->execute();
        $connection->prepare('INSERT INTO last_updates (table_name, timestamp) VALUES ("platforms", NOW()), ("sensors", NOW()), ("users", NOW()), ("divisions", NOW()), ("contacts", NOW()), ("settings", NOW()), ("event_filters", NOW()), ("stats", NOW()), ("services", NOW())')->execute();
    }
}
