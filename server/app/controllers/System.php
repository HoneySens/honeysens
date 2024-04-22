<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\Tools\SchemaTool;
use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\Utils;
use NoiseLabs\ToolKit\ConfigParser\Exception\NoOptionException;
use phpseclib3\File\X509;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use \Slim\Routing\RouteCollectorProxy;

class System extends RESTResource {

    const VERSION = '2.7.0';
    const ERR_UNKNOWN = 0;
    const ERR_CONFIG_WRITE = 1;

    static function registerRoutes($system, $em, $services, $config) {
        $system->get('', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new System($em, $services, $config);
            $response->getBody()->write(json_encode($controller->get()));
            return $response;
        });

        $system->get('/identify', function(Request $request, Response $response) use ($em, $services, $config) {
            // Predictable endpoint used to test the server's reachability (useful to figure out if a proxy actually works)
            $response->getBody()->write("HoneySens");
            return $response;
        });

        $system->delete('/events', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new System($em, $services, $config);
            $controller->removeAllEvents();
            $response->getBody()->write(json_encode([]));
            return $response;
        });

        $system->put('/ca', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new System($em, $services, $config);
            $controller->refreshCertificates($em);
            $response->getBody()->write(json_encode([]));
            return $response;
        });

        $system->post('/install', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new System($em, $services, $config);
            $request = $request->getBody()->getContents();
            V::json()->check($request);
            $installData = json_decode($request);
            $systemData = $controller->install($installData);
            $response->getBody()->write(json_encode($systemData));
            return $response;
        });
    }

    /**
     * Figures out if the installer hasn't been run on this installation yet.
     *
     * @param $config
     * @return bool
     */
    static function installRequired($config) {
        return $config->getBoolean('server', 'setup');
    }

    /**
     * Returns metadata about the server, some of it relevant specifically for the setup process.
     */
    public function get() {
        $config = $this->getConfig();
        // Fetch TLS cert common name
        $commonName = null;
        $x509 = new X509();
        try {
            // Manually select the first cert of a potential chain (see https://github.com/phpseclib/phpseclib/issues/708)
            $certs = preg_split('#-+BEGIN CERTIFICATE-+#', file_get_contents('/srv/tls/https.crt'));
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
            'build_id' => getenv('BUILD_ID'),
            'version' => $this::VERSION,
            'cert_cn' => $commonName,
            'setup' => $this::installRequired($config));
    }

    /**
     * Removes all events from the database, including archived ones.
     * Only admin users are permitted to execute that action.
     *
     * @throws ForbiddenException
     */
    public function removeAllEvents() {
        // This can only be invoked from an admin session
        if($_SESSION['user']['role'] != User::ROLE_ADMIN) {
            throw new ForbiddenException();
        }
        // QueryBuilder seems to ignore the cascade on delete specifications and fails with constraint checks,
        // if we just delete events here. As a workaround we will manually do the cascade stuff by deleting
        // referenced event details and packets first.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete('HoneySens\app\models\entities\EventDetail', 'ed');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\EventPacket', 'ep');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\Event', 'e');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\ArchivedEvent', 'ae');
        $qb->getQuery()->execute();
        $this->log('All events removed', LogEntry::RESOURCE_SYSTEM);
    }

    /**
     * Creates the database schema, a division and an administrative user
     * associated with that division.
     */
    private function initDBSchema($em, $adminEMail, $adminPassword, $divisionName) {
        $con = $em->getConnection();
        $schemaTool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        // Remove existing tables
        $schemaTool->dropSchema($classes);
        $con->query('DROP TABLE IF EXISTS `last_updates`');
        // Create schema
        $schemaTool->createSchema($classes);
        $this->addLastUpdatesTable($em);
        // Initial division
        $division = new Division();
        $division->setName($divisionName);
        $em->persist($division);
        // Default admin user
        $admin = new User();
        $admin
            ->setName('admin')
            ->setPassword($adminPassword)
            ->setDomain(User::DOMAIN_LOCAL)
            ->setFullName('Administrator')
            ->setEmail($adminEMail)
            ->setRole($admin::ROLE_ADMIN)
            ->addToDivision($division);
        $em->persist($admin);
        // Platforms
        $connection = $em->getConnection();
        $connection->prepare('INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("1", "bbb", "BeagleBone Black", "BeagleBone Black is a low-cost, community-supported development platform.", "bbb")')->execute();
        $connection->prepare('INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("2", "docker_x86", "Docker (x86)", "Dockerized sensor platform to be used on generic x86 hardware.", "docker_x86")')->execute();
        $em->flush();
    }

    /**
     * Performs the certificate recreation process:
     * 1. Creation of a new CA certificate from an existing key and regeneration of all sensor certificates (keeping their private keys).
     * 2. For self-signed setups: Creation of a new TLS certificate and signing that with the new CA.
     * 3. Restart of affected services.
     *
     * @param $em
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    function refreshCertificates($em) {
        // This can only be invoked from an admin session
        if($_SESSION['user']['role'] != User::ROLE_ADMIN) {
            throw new ForbiddenException();
        }
        $caKeyPath = APPLICATION_PATH . '/../data/CA/ca.key';
        $caCrtPath = APPLICATION_PATH . '/../data/CA/ca.crt';
        if(!file_exists($caKeyPath)) {
            throw new BadRequestException();
        }
        // Create new CA cert from existing private key
        exec('/etc/startup.d/02_regen_honeysens_ca.sh force');
        // Recreate TLS certificates
        exec('/etc/startup.d/03_regen_https_cert.sh force');
        $this->log('Certificates renewed', LogEntry::RESOURCE_SYSTEM);
        // Graceful httpd restart
        $pid = trim(file_get_contents('/var/run/apache2.pid'));
        exec('kill -USR1 ' . $pid);
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
     * @throws BadRequestException
     */
    function install($data) {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
        if(!$this::installRequired($config) || $em->getConnection()->getSchemaManager()->tablesExist(array('users'))) {
            throw new ForbiddenException();
        };
        // Validation
        V::objectType()
            ->attribute('email', Utils::emailValidator())
            ->attribute('password', V::stringType()->length(6, 255))
            ->attribute('serverEndpoint', V::stringType())
            ->attribute('divisionName', V::alnum()->length(1, 255))
            ->check($data);
        // Persistence
        $this->initDBSchema($em, $data->email, $data->password, $data->divisionName);
        $config->set('server', 'host', $data->serverEndpoint);
        $config->set('server', 'setup', 'false');
        try {
            $config->save();
        } catch (\ErrorException $e) {
            throw new BadRequestException($this::ERR_CONFIG_WRITE);
        }
        return array('cert_cn' => $data->serverEndpoint,
            'setup' => false);
    }

    private function addLastUpdatesTable($em) {
        // Add non-model table 'last_updates'
        $connection = $em->getConnection();
        $connection->prepare('CREATE TABLE last_updates(table_name VARCHAR(50) PRIMARY KEY, timestamp DATETIME)')->execute();
        $connection->prepare('INSERT INTO last_updates (table_name, timestamp) VALUES ("platforms", NOW()), ("sensors", NOW()), ("users", NOW()), ("divisions", NOW()), ("contacts", NOW()), ("settings", NOW()), ("event_filters", NOW()), ("stats", NOW()), ("services", NOW()), ("tasks", NOW())')->execute();
    }
}
