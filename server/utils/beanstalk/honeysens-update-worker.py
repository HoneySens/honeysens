#!/usr/bin/env python2

import beanstalkc
import ConfigParser
import json
import os
import pymysql
import sys


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
    config_version = config.get('server', 'config_version')
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

    # 18.12.01 -> NG
    if config_version == '18.12.01':
        print('Upgrading configuration: 18.12.01 -> NG')
        config.set('server', 'config_version', 'NG')
        config_version = 'NG'

    # Write new config file
    config.set('server', 'config_version', server_version)
    with open(sys.argv[1], 'wb') as f:
        config.write(f)

    # Removing update marker
    os.remove(marker_path)

    db.close()
    job.delete()
