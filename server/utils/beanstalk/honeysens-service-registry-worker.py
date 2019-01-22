#!/usr/bin/env python2

import beanstalkc
import ConfigParser
import json
import os
import shutil
import subprocess
import sys
import tarfile
import tempfile

if len(sys.argv) != 2:
    print('Usage: honeysens-service-registry-worker.py <appConfig>')
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
beanstalk.watch('honeysens-service-registry')

upload_path = '{}/data/upload'.format(config.get('server', 'app_path'))
if not os.path.isdir(upload_path):
    print('Error: Server upload directory not found')
    exit()

registry = '{}:{}'.format(config.get('registry', 'host'), config.get('registry', 'port'))

print('HoneySens Service Registry Worker\n')
print('  Upload directory: {}'.format(upload_path))
print('  Registry: {}'.format(registry))

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
    service_archive_path = job_data['archive_path']
    archive_name = job_data['archive_name']
    service_name = job_data['name']
    registry_name = '{}/{}'.format(registry, service_name)
    print('Service name: {}\nRegistry tag: {}'.format(service_name, registry_name))
    if not os.path.isfile(service_archive_path):
        print('Error: Archive file not found')
        job.delete()
        continue
    # Create temp directory
    working_dir = tempfile.mkdtemp()
    tar = tarfile.open(service_archive_path)
    tar.extractall(path=working_dir)
    tar.close()
    # Registry interaction
    print('Uploading image archive {}/{} to {}'.format(working_dir, archive_name, registry_name))
    try:
        subprocess.call(['/usr/bin/skopeo', 'copy', '--dest-tls-verify=false',
                         'docker-archive:{}/{}'.format(working_dir, archive_name),
                         'docker://{}'.format(registry_name)])
    except Exception as e:
        print('Error during image upload, cleaning up')
        shutil.rmtree(working_dir)
        os.remove(service_archive_path)
        continue
    print('Cleaning up')
    shutil.rmtree(working_dir)
    os.remove(service_archive_path)
    job.delete()
