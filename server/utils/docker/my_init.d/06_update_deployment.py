#!/usr/bin/python2 -u

import ConfigParser
import glob
import os
import pymysql
import re
import subprocess
import sys
import time
from OpenSSL import crypto


# Utility functions
def execute_sql(db, statements):
    errors = 0
    statement_count = len(statements)
    for s in statements:
        try:
            db.cursor().execute(s)
        except Exception:
            print('Statement error: {}'.format(s))
            errors += 1
    print('{} out of {} database statements performed successfully, {} errors'.format(statement_count - errors, statement_count, errors))

# Force UTF8 encoding
reload(sys)
sys.setdefaultencoding('utf-8')

# Global paths
BASE_PATH = '/opt/HoneySens'
APPLICATION_PATH = '{}/app'.format(BASE_PATH)
DATA_PATH = '{}/data'.format(BASE_PATH)

# Parse configuration
config_file = '{}/config.cfg'.format(DATA_PATH)
if not os.path.isfile(config_file):
    print('Updater: Config {} file not found'.format(config_file))
    exit(1)
else:
    print('Updater: Checking if an update to the local deployment is required...')
config = ConfigParser.ConfigParser()
# Preserve the case of config keys instead of forcing them lower-case
config.optionxform = str
config.readfp(open(config_file))

# Quit in case the data directory is not accessible
if not os.path.isdir(DATA_PATH):
    print('Updater: Error: Data directory not found')
    exit(1)

# Figure out server version
server_version = None
with open('{}/controllers/System.php'.format(APPLICATION_PATH)) as f:
    for line in f:
        if 'const VERSION' in line:
            server_version = re.sub("';", '', re.sub("const VERSION = '", '', line.strip()))
if server_version is None:
    print('Updater: Error: Could not identify server version')
    exit(1)
else:
    print('Updater: Server version: {}'.format(server_version))

# Figure out deployed version
if config.has_option('server', 'config_version'):
    config_version = config.get('server', 'config_version')
else:
    # 0.1.5 was the last version without configuration versioning, it's safe to assume this
    config_version = '0.1.5'
    config.set('server', 'config_version', config_version)
print('Updater: Config version: {}'.format(config_version))

# Determine if an update is required at all
if config_version == server_version:
    print('Updater: No update required')
    exit(0)
else:
    print('Updater: Performing update from {} to {}'.format(config_version, server_version))

# Check existence of required environment variables to connect to the database
if not all(v in os.environ for v in ['DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_NAME']):
    print('Updater: Error: Database connection environment variables are not set')
    exit(1)

# Initiate database connection
print('Updater: Connecting to database...')
while True:
    time.sleep(1)
    try:
        db = pymysql.connect(host=os.environ['DB_HOST'], port=int(os.environ['DB_PORT']),
                             user=os.environ['DB_USER'], passwd=os.environ['DB_PASSWORD'],
                             db=os.environ['DB_NAME'])
        c = db.cursor()
        if c.connection:
            break
        else:
            print('Updater: Waiting for database')
            continue
    except Exception as e:
        print('Updater: Waiting for database')
        continue

# 0.1.5 -> 0.2.0
if config_version == '0.1.5':
    print('Upgrading configuration: 0.1.5 -> 0.2.0')
    config.set('server', 'debug', 'false')
    config.set('server', 'setup', 'false')
    config.set('server', 'certfile', '/opt/HoneySens/data/ssl-cert.pem')
    config.remove_option('server', 'portHTTP')
    try:
        db.cursor().execute('ALTER TABLE sensors DROP serverEndpointPortHTTP')
        db.cursor().execute('INSERT INTO last_updates(table_name, timestamp) VALUES ("stats", 0)')
        db.commit()
    except Exception:
        pass
    config.set('server', 'config_version', '0.2.0')
    config_version = '0.2.0'
# 0.2.0 -> 0.2.1
if config_version == '0.2.0':
    print('Upgrading configuration: 0.2.0 -> 0.2.1')
    config.set('server', 'config_version', '0.2.1')
    config_version = '0.2.1'
# 0.2.1 -> 0.2.2
if config_version == '0.2.1':
    print('Upgrading configuration: 0.2.1 -> 0.2.2')
    config.set('server', 'config_version', '0.2.2')
    config_version = '0.2.2'
# 0.2.2 -> 0.2.3
if config_version == '0.2.2':
    print('Upgrading configuration: 0.2.2 -> 0.2.3')
    config.set('smtp', 'enabled', 'false')
    config.set('server', 'config_version', '0.2.3')
    config_version = '0.2.3'
# 0.2.3 -> 0.2.4
if config_version == '0.2.3':
    print('Upgrading configuration 0.2.3 -> 0.2.4')
    try:
        db.cursor().execute('ALTER TABLE contacts ADD sendAllEvents TINYINT(1) NOT NULL')
        db.cursor().execute('CREATE TABLE service_assignments (id INT AUTO_INCREMENT NOT NULL, sensor_id INT DEFAULT NULL, service_id INT DEFAULT NULL, revision_id INT DEFAULT NULL, INDEX IDX_FC107671A247991F (sensor_id), INDEX IDX_FC107671ED5CA9E6 (service_id), UNIQUE INDEX UNIQ_FC1076711DFA7C8F (revision_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB')
        db.cursor().execute('ALTER TABLE service_assignments ADD CONSTRAINT FK_FC107671A247991F FOREIGN KEY (sensor_id) REFERENCES sensors (id)')
        db.cursor().execute('ALTER TABLE service_assignments ADD CONSTRAINT FK_FC107671ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id)')
        db.cursor().execute('ALTER TABLE service_assignments ADD CONSTRAINT FK_FC1076711DFA7C8F FOREIGN KEY (revision_id) REFERENCES service_revisions (id)')
        db.cursor().execute('ALTER TABLE services ADD defaultRevision_id INT DEFAULT NULL')
        db.cursor().execute('ALTER TABLE services ADD CONSTRAINT FK_7332E169B00E5743 FOREIGN KEY (defaultRevision_id) REFERENCES service_revisions (id)')
        db.cursor().execute('CREATE UNIQUE INDEX UNIQ_7332E169B00E5743 ON services (defaultRevision_id)')
        db.commit()
    except Exception:
        pass
    config.add_section('registry')
    config.set('registry', 'port', '5000')
    config.set('registry', 'host', 'honeysens-registry')
    config.set('server', 'config_version', '0.2.4')
    config_version = '0.2.4'
# 0.2.4 -> 0.2.5
if config_version == '0.2.4':
    print('Upgrading configuration 0.2.4 -> 0.2.5')
    config.set('server', 'config_version', '0.2.5')
    config_version = '0.2.5'
# 0.2.5 -> 0.9.0
if config_version == '0.2.5':
    print('Upgrading configuration 0.2.5 -> 0.9.0')
    fw_path = '{}/firmware'.format(DATA_PATH)
    print('Cleaning old firmware files in {}'.format(fw_path))
    for f in glob.glob('{}/*'.format(fw_path)):
        print('  Removing {}'.format(f))
        try:
            os.remove(f)
        except Exception:
            print('Warning: Could not remove {}'.format(f))
    db_statements = [
        'CREATE TABLE platforms (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, defaultFirmwareRevision_id INT DEFAULT NULL, discr VARCHAR(255) NOT NULL, INDEX IDX_178186E343230D2B (defaultFirmwareRevision_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        'INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("1", "bbb", "BeagleBone Black", "BeagleBone Black is a low-cost, community-supported development platform.", "bbb")',
        'INSERT IGNORE INTO platforms(id, name, title, description, discr) VALUES ("2", "docker_x86", "Docker (x86)", "Dockerized sensor platform to be used on generic x86 hardware.", "docker_x86")',
        'CREATE TABLE firmware (id INT AUTO_INCREMENT NOT NULL, platform_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, version VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, changelog VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, INDEX IDX_D5ECD7C4FFE6496F (platform_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        'ALTER TABLE firmware ADD CONSTRAINT FK_D5ECD7C4FFE6496F FOREIGN KEY (platform_id) REFERENCES platforms (id)',
        'ALTER TABLE platforms ADD CONSTRAINT FK_178186E343230D2B FOREIGN KEY (defaultFirmwareRevision_id) REFERENCES firmware (id)',
        'ALTER TABLE sensors DROP FOREIGN KEY FK_D0D3FA9073F32DD8',
        'DROP INDEX IDX_D0D3FA9073F32DD8 ON sensors',
        'ALTER TABLE sensors DROP configuration_id',
        'DROP TABLE configs',
        'DROP TABLE images',
        'ALTER TABLE sensors ADD firmware_id INT DEFAULT NULL, ADD updateInterval INT DEFAULT NULL',
        'ALTER TABLE sensors ADD CONSTRAINT FK_D0D3FA90972206F2 FOREIGN KEY (firmware_id) REFERENCES firmware (id)',
        'CREATE INDEX IDX_D0D3FA90972206F2 ON sensors (firmware_id)',
        'DELETE FROM last_updates WHERE table_name = "configs" OR table_name = "images"',
        'INSERT INTO last_updates(table_name, timestamp) VALUES ("platforms", NOW())',
        'INSERT INTO last_updates(table_name, timestamp) VALUES ("services", NOW())',
        'ALTER TABLE services DROP FOREIGN KEY FK_7332E169B00E5743',
        'DROP INDEX UNIQ_7332E169B00E5743 ON services',
        'ALTER TABLE services ADD defaultRevision VARCHAR(255) DEFAULT NULL, DROP defaultRevision_id',
        'ALTER TABLE service_revisions ADD architecture VARCHAR(255) NOT NULL, ADD rawNetworkAccess TINYINT(1) NOT NULL, ADD portAssignment VARCHAR(255) NOT NULL, ADD catchAll TINYINT(1) NOT NULL'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('server', 'certfile', '/opt/HoneySens/data/https.chain.crt')
    config.add_section('sensors')
    config.set('sensors', 'update_interval', '5')
    config.set('server', 'config_version', '0.9.0')
    config_version = '0.9.0'
# 0.9.0 -> 1.0.0
if config_version == '0.9.0':
    print('Upgrading configuration 0.9.0 -> 1.0.0')
    db.cursor().execute('ALTER TABLE statuslogs ADD diskUsage INT NOT NULL, ADD diskTotal INT NOT NULL')
    config.set('server', 'config_version', '1.0.0')
    config_version = '1.0.0'
# 1.0.0 -> 1.0.1
if config_version == '1.0.0':
    print('Upgrading configuration 1.0.0 -> 1.0.1')
    # Reissue certificates with sha256 digest
    ca_crt = crypto.load_certificate(crypto.FILETYPE_PEM, open('{}/CA/ca.crt'.format(DATA_PATH), 'rt').read())
    ca_key = crypto.load_privatekey(crypto.FILETYPE_PEM, open('{}/CA/ca.key'.format(DATA_PATH), 'rt').read())
    update_statements = []
    cursor = pymysql.cursors.DictCursor(db)
    cursor.execute('SELECT s.id, c.id, c.privateKey FROM sensors s INNER JOIN certs c ON s.cert_id = c.id')
    for row in cursor.fetchall():
        pkey = crypto.load_privatekey(crypto.FILETYPE_PEM, row['privateKey'])
        req = crypto.X509Req()
        setattr(req.get_subject(), 'CN', 's{}'.format(row['id']))
        req.set_pubkey(pkey)
        req.sign(pkey, 'sha256')
        cert = crypto.X509()
        cert.gmtime_adj_notBefore(0)
        cert.gmtime_adj_notAfter(315360000)
        cert.set_serial_number(0)
        cert.set_issuer(ca_crt.get_subject())
        cert.set_subject(req.get_subject())
        cert.set_pubkey(req.get_pubkey())
        cert.sign(ca_key, 'sha256')
        update_statements.append('UPDATE certs SET content="{}" WHERE id={}'.format(crypto.dump_certificate(crypto.FILETYPE_PEM, cert), row['c.id']))
    execute_sql(db, update_statements)
    db.commit()
    config.set('server', 'config_version', '1.0.1')
    config_version = '1.0.1'
# 1.0.1 -> 1.0.2
if config_version == '1.0.1':
    print('Upgrading configuration 1.0.1 -> 1.0.2')
    config.set('smtp', 'port', '25')
    config.set('server', 'config_version', '1.0.2')
    config_version = '1.0.2'
# 1.0.2 -> 1.0.3
if config_version == '1.0.2':
    print('Upgrading configuration 1.0.2 -> 1.0.3')
    config.set('server', 'config_version', '1.0.3')
    config_version = '1.0.3'
# 1.0.3 -> 1.0.4
if config_version == '1.0.3':
    print('Upgrading configuration 1.0.3 -> 1.0.4')
    config.set('server', 'config_version', '1.0.4')
    config_version = '1.0.4'
# 1.0.4 -> 2.0.0
if config_version == '1.0.4':
    print('Upgrading configuration 1.0.4 -> 2.0.0')
    config.set('sensors', 'service_network', '10.10.10.0/24')
    db_statements = [
        'ALTER TABLE sensors ADD serviceNetwork VARCHAR(255) DEFAULT NULL',
        'ALTER TABLE statuslogs ADD serviceStatus VARCHAR(255) DEFAULT NULL'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('server', 'config_version', '2.0.0')
    config_version = '2.0.0'
# 2.0.0 -> 2.1.0
if config_version == '2.0.0':
    print('Upgrading configuration 2.0.0 -> 2.1.0')
    db_statements = [
        'ALTER TABLE users ADD legacyPassword VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL',
        'ALTER TABLE users ADD domain INT NOT NULL, ADD fullName VARCHAR(255) DEFAULT NULL',
        'UPDATE users SET legacyPassword=password,password=NULL',
        'CREATE TABLE tasks (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, type INT NOT NULL, status INT NOT NULL, params LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json_array)", result LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json_array)", INDEX IDX_50586597A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        'ALTER TABLE tasks ADD CONSTRAINT FK_50586597A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)',
        'INSERT INTO last_updates(table_name, timestamp) VALUES ("tasks", NOW())',
        'ALTER TABLE firmware CHANGE source source VARCHAR(255) DEFAULT NULL'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('server', 'data_path', '/opt/HoneySens/data')
    config.add_section('ldap')
    config.set('ldap', 'enabled', 'false')
    config.set('ldap', 'server', '')
    config.set('ldap', 'port', '')
    config.set('ldap', 'encryption', '0')
    config.set('ldap', 'template', '')
    config.add_section('misc')
    config.set('misc', 'restrict_manager_role', 'false')
    config.set('server', 'config_version', '2.1.0')
    config_version = '2.1.0'
# 2.1.0 -> 2.2.0
if config_version == '2.1.0':
    print('Upgrading configuration 2.1.0 -> 2.2.0')
    db_statements = [
        'ALTER TABLE certs CHANGE privateKey privateKey LONGTEXT DEFAULT NULL',
        'ALTER TABLE sensors ADD EAPOLMode INT NOT NULL, ADD EAPOLIdentity VARCHAR(255) DEFAULT NULL, ADD EAPOLPassword VARCHAR(255) DEFAULT NULL, ADD EAPOLAnonymousIdentity VARCHAR(255) DEFAULT NULL, ADD EAPOLClientCertPassphrase VARCHAR(255) DEFAULT NULL, ADD EAPOLCACert_id INT DEFAULT NULL, ADD EAPOLClientCert_id INT DEFAULT NULL',
        'ALTER TABLE sensors ADD CONSTRAINT FK_D0D3FA90584A6C84 FOREIGN KEY (EAPOLCACert_id) REFERENCES certs (id)',
        'ALTER TABLE sensors ADD CONSTRAINT FK_D0D3FA90808C7157 FOREIGN KEY (EAPOLClientCert_id) REFERENCES certs (id)',
        'CREATE UNIQUE INDEX UNIQ_D0D3FA90584A6C84 ON sensors (EAPOLCACert_id)',
        'CREATE UNIQUE INDEX UNIQ_D0D3FA90808C7157 ON sensors (EAPOLClientCert_id)'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.remove_section('beanstalkd')
    config.remove_section('database')
    config.remove_section('registry')
    config.remove_option('server', 'app_path')
    config.remove_option('server', 'data_path')
    config.remove_option('server', 'certfile')
    config.add_section('syslog')
    config.set('syslog', 'enabled', 'false')
    config.set('syslog', 'server', '')
    config.set('syslog', 'port', '')
    config.set('syslog', 'transport', '0')
    config.set('syslog', 'facility', '1')
    config.set('syslog', 'priority', '6')
    config.set('server', 'config_version', '2.2.0')
    config_version = '2.2.0'
# 2.2.0 -> 2.3.0
if config_version == '2.2.0':
    print('Upgrading configuration 2.2.0 -> 2.3.0')
    db_statements = [
        'ALTER TABLE statuslogs ADD runningSince DATETIME DEFAULT NULL',
        ('ALTER TABLE statuslogs CHANGE ip ip VARCHAR(255) DEFAULT NULL, '
         'CHANGE freeMem freeMem INT DEFAULT NULL, CHANGE diskUsage diskUsage INT DEFAULT NULL, '
         'CHANGE diskTotal diskTotal INT DEFAULT NULL, CHANGE swVersion swVersion VARCHAR(255) DEFAULT NULL'),
        'ALTER TABLE contacts ADD sendSensorTimeouts TINYINT(1) NOT NULL',
        'ALTER TABLE users ADD notifyOnCAExpiration TINYINT(1) NOT NULL',
        ('CREATE TABLE logs (id INT AUTO_INCREMENT NOT NULL, timestamp DATETIME NOT NULL, userID INT DEFAULT NULL, '
         'resourceID INT DEFAULT NULL, resourceType INT NOT NULL, message VARCHAR(255) NOT NULL, '
         'PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'),
        'ALTER TABLE event_filters ADD description VARCHAR(255) DEFAULT NULL'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('sensors', 'timeout_threshold', '1')
    config.set('smtp', 'encryption', '0')
    config.set('misc', 'api_log_keep_days', '7')
    config.set('misc', 'require_event_comment', 'false')
    config.set('misc', 'require_filter_description', 'false')
    config.set('server', 'config_version', '2.3.0')
    config_version = '2.3.0'
# 2.3.0 -> devel
if config_version == '2.3.0':
    print('Upgrading configuration 2.3.0 -> devel')
    db_statements = [
        'ALTER TABLE users ADD requirePasswordChange TINYINT(1) NOT NULL',
        'ALTER TABLE event_filters ADD enabled TINYINT(1) NOT NULL',
        'UPDATE event_filters SET enabled=1',
        'CREATE TABLE template_overlays (type INT NOT NULL, template LONGTEXT NOT NULL, PRIMARY KEY(type)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        'CREATE TABLE archived_events (id INT AUTO_INCREMENT NOT NULL, division_id INT DEFAULT NULL, oid INT NOT NULL, timestamp DATETIME NOT NULL, sensor VARCHAR(255) NOT NULL, divisionName VARCHAR(255) DEFAULT NULL, service INT NOT NULL, classification INT NOT NULL, source VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, status INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, lastModificationTime DATETIME DEFAULT NULL, archiveTime DATETIME NOT NULL, details LONGTEXT NOT NULL, packets LONGTEXT NOT NULL, INDEX IDX_1F331E1D41859289 (division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        'ALTER TABLE archived_events ADD CONSTRAINT FK_1F331E1D41859289 FOREIGN KEY (division_id) REFERENCES divisions (id)',
        'ALTER TABLE events ADD lastModificationTime DATETIME DEFAULT NULL',
        'ALTER TABLE sensors ADD networkDHCPHostname VARCHAR(255) DEFAULT NULL'
    ]
    execute_sql(db, db_statements)
    db.commit()
    subprocess.run(['/etc/my_init.d/03_regen_https_cert.sh', 'force'])
    config.set('misc', 'archive_auto_days', '7')
    config.set('misc', 'archive_keep_days', '30')
    config.set('misc', 'archive_prefer', 'true')
    config_version = 'devel'


# Write new config file
with open(config_file, 'w') as f:
    config.write(f)

# Close db connection
db.close()
print('Updater: Done')
