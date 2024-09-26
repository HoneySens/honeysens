<?php
namespace HoneySens\app\services;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use HoneySens\app\models\constants\AuthDomain;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\SystemException;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use phpseclib3\File\X509;

class SystemService extends Service {

    const VERSION = '2.7.0';
    const ERR_CONFIG_WRITE = 1;

    private ConfigParser $config;
    private LogService $logger;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger) {
        parent::__construct($em);
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Figures out whether the installer hasn't been run on this installation yet.
     */
    public function installRequired(): bool {
        return $this->config->getBoolean('server', 'setup');
    }

    /**
     * Returns metadata about the server, some of it relevant specifically for the setup process.
     * The provided TLS certificate will be parsed to get the server's common name (CN).
     *
     * @param string $serverTLSCert PEM-formatted TLS certificate
     */
    public function getSystemInfo(string $serverTLSCert): array {
        // Fetch TLS cert common name
        $commonName = null;
        $x509 = new X509();
        try {
            // Manually select the first cert of a potential chain (see https://github.com/phpseclib/phpseclib/issues/708)
            $certs = preg_split('#-+BEGIN CERTIFICATE-+#', $serverTLSCert);
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
        } catch(\Exception) {}
        return array(
            'build_id' => getenv('BUILD_ID'),
            'version' => self::VERSION,
            'cert_cn' => $commonName,
            'setup' => $this->installRequired());
    }

    /**
     * Removes all events from the database, including archived ones.
     * Only admin users are permitted to call this method.
     *
     * @param User $user User calling this method
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function removeAllEvents(User $user): void {
        if($user->role !== UserRole::ADMIN) throw new ForbiddenException();
        // QueryBuilder seems to ignore the cascade on delete specifications and fails with constraint checks,
        // if we just delete events here. As a workaround we will manually do the cascade stuff by deleting
        // referenced event details and packets first.
        $qb = $this->em->createQueryBuilder();
        $qb->delete('HoneySens\app\models\entities\EventDetail', 'ed');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\EventPacket', 'ep');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\Event', 'e');
        $qb->getQuery()->execute();
        $qb->delete('HoneySens\app\models\entities\ArchivedEvent', 'ae');
        $qb->getQuery()->execute();
        $this->logger->log('All events removed', LogResource::SYSTEM);
    }


    /**
     * Performs the certificate recreation process:
     * 1. Creation of a new CA certificate from an existing key and regeneration of all sensor certificates (keeping their private keys).
     * 2. For self-signed setups: Creation of a new TLS certificate and signing that with the new CA.
     * 3. Restart of affected services.
     * Only admin users are permitted to call this method.
     *
     * @param User $user User calling this method
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function refreshCertificates(User $user): void {
        if($user->role !== UserRole::ADMIN) throw new ForbiddenException();
        $caKeyPath = APPLICATION_PATH . '/../data/CA/ca.key';
        if(!file_exists($caKeyPath)) throw new BadRequestException();
        // Create new CA cert from existing private key
        exec('/etc/startup.d/02_regen_honeysens_ca.sh force');
        // Recreate TLS certificates
        exec('/etc/startup.d/03_regen_https_cert.sh force');
        $this->logger->log('Certificates renewed', LogResource::SYSTEM);
        // Graceful httpd restart
        $pid = trim(file_get_contents('/var/run/apache2.pid'));
        exec('kill -USR1 ' . $pid);
    }

    /**
     * Performs the initial configuration of a newly installed system.
     * Creates an administrative account, an initial division and sets the server hostname.
     *
     * @param string $adminEmail Administrator e-mail address
     * @param string $adminPassword Administrator password
     * @param string $serverHostname Server DNS hostname
     * @param string $initialDivision Name of the newly created division
     * @throws BadRequestException
     * @throws SystemException
     */
    public function install(string $adminEmail, string $adminPassword, string $serverHostname, string $initialDivision): array {
        try {
            if (!$this->installRequired() || $this->em->getConnection()->createSchemaManager()->tablesExist(array('users'))) {
                throw new ForbiddenException();
            };
            $this->initDBSchema($adminEmail, $adminPassword, $initialDivision);
        } catch(\Exception $e) {
            throw new SystemException($e);
        }
        $this->config->set('server', 'host', $serverHostname);
        $this->config->set('server', 'setup', 'false');
        try {
            $this->config->save();
        } catch (\Exception) {
            throw new BadRequestException(self::ERR_CONFIG_WRITE);
        }
        return array('cert_cn' => $serverHostname,
            'setup' => false);
    }

    /**
     * Creates the database schema, a division and an administrative user
     * associated with that division.
     *
     * @param string $adminEMail Administrator e-mail address
     * @param string $adminPassword Administrator password
     * @param string $divisionName Name of the newly created division
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ToolsException
     */
    private function initDBSchema(string $adminEMail, string $adminPassword, string $divisionName): void {
        $con = $this->em->getConnection();
        $schemaTool = new SchemaTool($this->em);
        $classes = $this->em->getMetadataFactory()->getAllMetadata();
        // Remove existing tables
        $schemaTool->dropSchema($classes);
        $con->executeStatement('DROP TABLE IF EXISTS `last_updates`');
        // Create schema
        $schemaTool->createSchema($classes);
        $this->addLastUpdatesTable();
        // Initial division
        $division = new Division();
        $division->setName($divisionName);
        $this->em->persist($division);
        // Default admin user
        $admin = new User();
        $admin->setPassword($adminPassword);
        $admin->name = 'admin';
        $admin->fullName = 'Administrator';
        $admin->domain = AuthDomain::LOCAL;
        $admin->email = $adminEMail;
        $admin->role = UserRole::ADMIN;
        $admin->addToDivision($division);
        $this->em->persist($admin);
        // Add Platforms
        $connection = $this->em->getConnection();
        $connection->executeStatement('INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("1", "bbb", "BeagleBone Black", "BeagleBone Black is a low-cost, community-supported development platform.", "bbb")');
        $connection->executeStatement('INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("2", "docker_x86", "Docker (x86)", "Dockerized sensor platform to be used on generic x86 hardware.", "docker_x86")');
        $this->em->flush();
    }

    /**
     * Adds a non-model table called 'last_updates'
     * that tracks update timestamps for each entity table.
     *
     * @throws DBALException
     */
    private function addLastUpdatesTable(): void {
        $connection = $this->em->getConnection();
        $connection->executeStatement('CREATE TABLE last_updates(table_name VARCHAR(50) PRIMARY KEY, timestamp DATETIME)');
        $connection->executeStatement('INSERT INTO last_updates (table_name, timestamp) VALUES ("platforms", NOW()), ("sensors", NOW()), ("users", NOW()), ("divisions", NOW()), ("contacts", NOW()), ("settings", NOW()), ("event_filters", NOW()), ("stats", NOW()), ("services", NOW()), ("tasks", NOW())');
    }
}
