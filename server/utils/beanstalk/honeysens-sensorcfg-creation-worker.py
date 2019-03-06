#!/usr/bin/env python2

import sys
import os
import ConfigParser
import beanstalkc
import json
import tempfile
import pymysql
import shutil
import tarfile

if len(sys.argv) != 2:
    print('Usage: honeysens-sensorcfg-creation-worker.py <appConfig>')
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
beanstalk.watch('honeysens-sensorcfg')

config_path = '{}/data/configs'.format(config.get('server', 'app_path'))
if not os.path.isdir(config_path):
    print('Error: Sensor config directory not found')
    exit()

server_cert = config.get('server', 'certfile')
if not os.path.isfile(server_cert):
    print('Error: Server certificate not found')
    exit()

print('HoneySens Sensor Configuration Archive Creation Worker\n')
print('  Configuration archive directory: {}\n  Server certificate: {}\n'.format(config_path, server_cert))

while True:
    print('Worker: READY')
    job = beanstalk.reserve()
    try:
        job_data = json.loads(job.body)
    except ValueError:
        print('Error: Invalid input data, removing job')
        job.delete()
        continue
    print('----------------------------------------\nJob received')
    print('  Hostname: {}\n  Sensor name: {}\n  Location: {}\n'.format(job_data['hostname'], job_data['name'], job_data['location']))
    # Initiate db connection
    db = pymysql.connect(host=config.get('database', 'host'), port=int(config.get('database', 'port')),
                         user=config.get('database', 'user'), passwd=config.get('database', 'password'),
                         db=config.get('database', 'dbname'))
    # Check sensor existence
    db_cursor = db.cursor()
    db_cursor.execute('SELECT COUNT(*) FROM sensors WHERE id = {}'.format(job_data['id']))
    result = db_cursor.fetchone()
    if result[0] != 1:
        print('Error: Sensor with ID {} not found in database'.format(job_data['id']))
        continue
    # Set process status to "creating" in db
    db_cursor.execute('UPDATE sensors SET configArchiveStatus = {} WHERE id = {}'.format(2, job_data['id']))
    db_cursor.execute('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "sensors"')
    db.commit()

    working_dir = tempfile.mkdtemp()
    print('Working directory: {}'.format(working_dir))
    print('Writing sensor certificate to {}/cert.pem'.format(working_dir))
    with open('{}/cert.pem'.format(working_dir), 'w') as f:
        f.write(job_data['cert'])
    print('Writing sensor key to {}/key.pem'.format(working_dir))
    with open('{}/key.pem'.format(working_dir), 'w') as f:
        f.write(job_data['key'])
    print('Copying server certificate bundle: {} -> {}/server-cert.pem'.format(server_cert, working_dir))
    shutil.copy(server_cert, '{}/server-cert.pem'.format(working_dir))
    print('Writing honeysens.cfg')
    sensor_config = ConfigParser.ConfigParser()
    sensor_config.add_section('server')
    sensor_config.add_section('general')
    sensor_config.add_section('network')
    sensor_config.add_section('mac')
    sensor_config.add_section('proxy')
    sensor_config.set('server', 'host', job_data['server_endpoint_host'])
    sensor_config.set('server', 'name', job_data['server_endpoint_name'])
    sensor_config.set('server', 'port_https', job_data['server_endpoint_port_https'])
    sensor_config.set('server', 'interval', 1)  # Use an update interval of one minute to quickly connect to the server
    sensor_config.set('server', 'certfile', 'server-cert.pem')
    sensor_config.set('general', 'sensor_id', job_data['id'])
    sensor_config.set('general', 'hostname', job_data['hostname'])
    sensor_config.set('general', 'certfile', 'cert.pem')
    sensor_config.set('general', 'keyfile', 'key.pem')
    sensor_config.set('general', 'service_network', job_data['service_network'])
    sensor_config.set('network', 'mode', job_data['network_ip_mode'])
    sensor_config.set('network', 'address', job_data['network_ip_address'])
    sensor_config.set('network', 'netmask', job_data['network_ip_netmask'])
    sensor_config.set('network', 'gateway', job_data['network_ip_gateway'])
    sensor_config.set('network', 'dns', job_data['network_ip_dns'])
    sensor_config.set('mac', 'mode', job_data['network_mac_mode'])
    sensor_config.set('mac', 'address', job_data['network_mac_address'])
    sensor_config.set('proxy', 'mode', job_data['proxy_mode'])
    sensor_config.set('proxy', 'host', job_data['proxy_host'])
    sensor_config.set('proxy', 'port', job_data['proxy_port'])
    sensor_config.set('proxy', 'user', job_data['proxy_user'])
    sensor_config.set('proxy', 'password', job_data['proxy_password'])
    with open('{}/honeysens.cfg'.format(working_dir), 'wb') as sensor_config_file:
        sensor_config.write(sensor_config_file)

    print('Packaging configuration archive {}/{}.tar.gz'.format(config_path, job_data['hostname']))
    with tarfile.open('{}/{}.tar.gz'.format(config_path, job_data['hostname']), 'w:gz') as config_archive:
        for name in ['cert.pem', 'key.pem', 'server-cert.pem', 'honeysens.cfg']:
            config_archive.add('{}/{}'.format(working_dir, name), name)

    print('Job completed, updating database and cleaning up\n----------------------------------------\n')
    db_cursor.execute('UPDATE sensors SET configArchiveStatus = {} WHERE id = {}'.format(3, job_data['id']))
    db_cursor.execute('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "sensors"')
    db.commit()
    db.close()
    shutil.rmtree(working_dir)
    db_cursor.close()
    job.delete()
