#!/usr/bin/python3 -u

import configparser
import os
import pymysql
import re
import time


# Utility functions
def connect_to_db(host, port, user, password, db_name):
    while True:
        time.sleep(1)
        try:
            db = pymysql.connect(host=host, port=port, user=user, password=password, database=db_name)
            c = db.cursor()
            if c.connection:
                return db
            else:
                print('Updater: Waiting for database')
                continue
        except Exception as e:
            print('Updater: Waiting for database')
            continue


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


# Global paths
APPLICATION_PATH = os.environ['HS_APP_PATH']
DATA_PATH = os.environ['HS_DATA_PATH']

# Parse configuration
config_file = '{}/config.cfg'.format(DATA_PATH)
if not os.path.isfile(config_file):
    print('Updater: Config {} file not found'.format(config_file))
    exit(1)
else:
    print('Updater: Checking if an update to the local deployment is required...')
config = configparser.ConfigParser()
# Preserve the case of config keys instead of forcing them lower-case
config.optionxform = str
config.read_file(open(config_file))

# Quit in case the data directory is not accessible
if not os.path.isdir(DATA_PATH):
    print('Updater: Error: Data directory not found')
    exit(1)

# Figure out server version
server_version = None
with open('{}/api/app/services/SystemService.php'.format(APPLICATION_PATH)) as f:
    for line in f:
        if 'const VERSION' in line:
            server_version = re.sub("';", '', re.sub("const VERSION = '", '', line.strip()))
if server_version is None:
    print('Updater: Error: Could not identify server version')
    exit(1)
else:
    print('Updater: Server version: {}'.format(server_version))

# Figure out deployed version
config_version = config.get('server', 'config_version')
print('Updater: Config version: {}'.format(config_version))

# Determine if an update is required at all
if config_version == server_version:
    print('Updater: No update required')
    exit(0)
else:
    print('Updater: Performing update from {} to {}'.format(config_version, server_version))

# Check existence of required environment variables to connect to the database
if not all(v in os.environ for v in ['HS_DB_HOST', 'HS_DB_PORT', 'HS_DB_USER', 'HS_DB_PASSWORD', 'HS_DB_NAME']):
    print('Updater: Error: Database connection environment variables are not set')
    exit(1)

# Initiate database connection
print('Updater: Connecting to database...')
db = connect_to_db(host=os.environ['HS_DB_HOST'], port=int(os.environ['HS_DB_PORT']),
                   user=os.environ['HS_DB_USER'], password=os.environ['HS_DB_PASSWORD'],
                   db_name=os.environ['HS_DB_NAME'])

# 2.4.0 -> 2.5.0
if config_version == '2.4.0':
    print('Upgrading configuration 2.4.0 -> 2.5.0')
    db_statements = [
        'ALTER TABLE sensors DROP FOREIGN KEY FK_D0D3FA9081448FA9',
        'DELETE FROM certs WHERE id IN (SELECT cert_id FROM sensors)',
        'DROP INDEX UNIQ_D0D3FA9081448FA9 ON sensors',
        'ALTER TABLE sensors DROP cert_id'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('server', 'config_version', '2.5.0')
    config_version = '2.5.0'

# 2.5.0 -> 2.6.0
if config_version == '2.5.0':
    print('Upgrading configuration 2.5.0 -> 2.6.0')
    config.set('misc', 'prevent_event_deletion_by_managers', config.get('misc', 'restrict_manager_role'))
    config.set('misc', 'prevent_sensor_deletion_by_managers', config.get('misc', 'restrict_manager_role'))
    config.remove_option('misc', 'restrict_manager_role')
    config.set('server', 'config_version', '2.6.0')
    config_version = '2.6.0'

# 2.6.0 -> 2.6.1
if config_version == '2.6.0':
    print('Upgrading configuration 2.6.0 -> 2.6.1')
    config.set('server', 'config_version', '2.6.1')
    config_version = '2.6.1'

# 2.6.1 -> 2.7.0
if config_version == '2.6.1':
    print('Upgrading configuration 2.6.1 -> 2.7.0')
    db_statements = [
        'ALTER TABLE users CHANGE notifyoncaexpiration notifyOnSystemState TINYINT(1) NOT NULL',
        'CREATE INDEX timestamp_idx ON events (timestamp)',
        'CREATE INDEX timestamp_idx ON archived_events (timestamp)'
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('server', 'config_version', '2.7.0')
    config_version = '2.7.0'

# 2.7.0 -> 2.8.0
if config_version == '2.7.0':
    print('Upgrading configuration 2.7.0 -> 2.8.0')
    print('Migrating DB users to use the cached_sha2_password auth plugin')
    db.close()
    db_root = connect_to_db(host=os.environ['HS_DB_HOST'], port=int(os.environ['HS_DB_PORT']),
                            user="root", password=os.environ['HS_DB_ROOT_PASSWORD'],
                            db_name=os.environ['HS_DB_NAME'])
    db_root_statements = [
        f"ALTER USER 'root'@'%' IDENTIFIED WITH caching_sha2_password BY '{os.environ['HS_DB_ROOT_PASSWORD']}'",
        f"ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '{os.environ['HS_DB_ROOT_PASSWORD']}'",
        f"ALTER USER 'honeysens'@'%' IDENTIFIED WITH caching_sha2_password BY '{os.environ['HS_DB_PASSWORD']}'",
    ]
    execute_sql(db_root, db_root_statements)
    db_root.close()

    db = connect_to_db(host=os.environ['HS_DB_HOST'], port=int(os.environ['HS_DB_PORT']),
                       user=os.environ['HS_DB_USER'], password=os.environ['HS_DB_PASSWORD'],
                       db_name=os.environ['HS_DB_NAME'])
    db_statements = [
        'ALTER TABLE tasks CHANGE params params JSON DEFAULT NULL, CHANGE result result JSON DEFAULT NULL',
        'ALTER TABLE sensors DROP configArchiveStatus',
        'ALTER TABLE firmware DROP source',
        'ALTER TABLE event_filters DROP type'
    ]
    execute_sql(db, db_statements)
    db.commit()
    for section, key, default in [
        ('ldap', 'port', 389),
        ('smtp', 'port', 587),
        ('syslog', 'transport', 0),
        ('syslog', 'facility', 1),
        ('syslog', 'port', 514),
        ('syslog', 'priority', 6)
    ]:
        if config.get(section, key) == '':
            config.set(section, key, str(default))
    config.set('server', 'config_version', '2.8.0')
    config_version = '2.8.0'
# 2.8.0 -> 2.9.0
if config_version == '2.8.0':
    print('Upgrading configuration 2.8.0 -> 2.9.0')
    db_statements = [
        'ALTER TABLE events CHANGE comment comment VARCHAR(1000) DEFAULT NULL',
    ]
    execute_sql(db, db_statements)
    db.commit()
    config.set('server', 'config_version', '2.9.0')
    config_version = '2.9.0'

# Write new config file
with open(config_file, 'w') as f:
    config.write(f)

# Close db connection
db.close()
print('Updater: Done')
