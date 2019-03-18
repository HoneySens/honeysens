#!/usr/bin/env python2

import beanstalkc
import ConfigParser
import glob
import json
import os
import pymysql
import sys
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


if len(sys.argv) != 2:
    print('Usage: honeysens-update-worker.py <appConfig>')
    exit()

config_file = sys.argv[1]
if not os.path.isfile(config_file):
    print('Error: Config file not found')
    exit()

reload(sys)
sys.setdefaultencoding('utf-8')
config = ConfigParser.ConfigParser()
config.readfp(open(config_file))
beanstalk = beanstalkc.Connection(host=config.get('beanstalkd', 'host'), port=int(config.get('beanstalkd', 'port')))
beanstalk.watch('honeysens-update')

data_path = '{}/data'.format(config.get('server', 'app_path'))
if not os.path.isdir(data_path):
    print('Error: Data directory not found')
    exit()

print('HoneySens Update Worker\n')

while True:
    print('Worker: READY')
    job = beanstalk.reserve()

    # Parse job data
    try:
        job_data = json.loads(job.body)
    except ValueError:
        print('Error: Invalid input data, removing job')
        job.delete()
        continue
    # Reread configuration
    config = ConfigParser.ConfigParser()
    # Preserve the case of keys instead of forcing them lower-case
    config.optionxform = str
    config.readfp(open(config_file))
    # Initiate db connection
    db = pymysql.connect(host=config.get('database', 'host'), port=int(config.get('database', 'port')),
                         user=config.get('database', 'user'), passwd=config.get('database', 'password'),
                         db=config.get('database', 'dbname'))
    server_version = job_data['server_version']
    if config.has_option('server', 'config_version'):
        config_version = config.get('server', 'config_version')
    else:
        # 0.1.5 was the last version without configuration versioning, it's safe to assume this
        config_version = '0.1.5'
        config.set('server', 'config_version', config_version)
    print('----------------------------------------\nJob received')
    print('  Server version: {}'.format(server_version))
    print('  Config version: {}'.format(config_version))

    # Determine if an update is required at all
    if config_version == server_version:
        print('Error: No update necessary')
        job.delete()
        continue

    # Create update marker
    marker_path = '{}/UPDATE'.format(data_path)
    if not os.path.isfile(marker_path):
        print('Creating update marker as {}'.format(marker_path))
        open(marker_path, 'w+')

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
        fw_path = '{}/data/firmware'.format(config.get('server', 'app_path'))
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
        ca_crt = crypto.load_certificate(crypto.FILETYPE_PEM, open('{}/CA/ca.crt'.format(data_path), 'rt').read())
        ca_key = crypto.load_privatekey(crypto.FILETYPE_PEM, open('{}/CA/ca.key'.format(data_path), 'rt').read())
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
    # 1.0.4 -> next
    if config_version == '1.0.4':
        print('Upgrading configuration 1.0.4 -> next')
        config.set('sensor', 'service_network', '10.10.10.0/24')
        db.cursor().execute('ALTER TABLE sensors ADD serviceNetwork VARCHAR(255) DEFAULT NULL')
        db.cursor().execute('ALTER TABLE statuslogs ADD serviceStatus VARCHAR(255) DEFAULT NULL')
        config_version = 'next'

    # Write new config file
    config.set('server', 'config_version', server_version)
    with open(sys.argv[1], 'wb') as f:
        config.write(f)

    # Removing update marker
    os.remove(marker_path)

    db.close()
    job.delete()
