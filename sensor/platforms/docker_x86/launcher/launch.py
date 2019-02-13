#!/usr/bin/env python2

from __future__ import absolute_import

import argparse
import os
import subprocess


launcher_dir = os.path.dirname(os.path.abspath(__file__))
parser = argparse.ArgumentParser()
parser.add_argument('action', help='The action to undertake', choices=['start', 'update'])
parser.add_argument('config', help='Sensor configuration archive')
parser.add_argument('update_path', help='Local path to bind into the container for image updates')
parser.add_argument('--image', help='Firmware image to use, e.g. honeysens/sensorx86:<version>')
parser.add_argument('--network', help='Docker network to connect the sensor container to (see: docker help network, default: bridge)')
parser.add_argument('--interface', help='The honepot interface to use within the container (default: eth0)')
parser.add_argument('--attach', help='Attach to the docker container to view its output', action="store_true")
args = parser.parse_args()
action = args.action
config_archive = args.config
update_path = args.update_path
target_image = args.image
network = args.network
interface = args.interface
attach = args.attach
container_name = 'sensor'
image_prefix = 'honeysens/sensorx86'
FNULL = open(os.devnull, 'w')

# Check configuration archive existence
if not os.path.isfile(config_archive):
    print('Error: Could not open configuration archive {}'.format(config_archive))
    exit(1)

# Check update path
if not os.path.isdir(update_path):
    print('Error: update path is not a directory')
    exit(1)

# Find docker binary
try:
    bin_docker = subprocess.check_output(['which', 'docker']).strip()
    print('Docker binary: {}'.format(bin_docker))
except Exception as e:
    print('Error: Docker binary not found')
    exit(1)

# Either start the given image or perform an update
if action == 'start':
    if target_image is None:
        print('Error: --image is required')
        exit(1)
elif action == 'update':
    print('Searching for firmware files in {}'.format(update_path))
    if not os.path.isfile('{}/firmware'.format(update_path)) or not os.path.isfile('{}/tag'.format(update_path)):
        print('No firmware files found')
        exit(0)
    print('Loading new firmware {}/firmware'.format(update_path))
    subprocess.call([bin_docker, 'load', '-i', '{}/firmware'.format(update_path)])
    with open('{}/tag'.format(update_path)) as f:
        target_image = '{}:{}'.format(image_prefix, f.read().strip())
    os.remove('{}/firmware'.format(update_path))
    os.remove('{}/tag'.format(update_path))

# Analyze system status
if subprocess.call([bin_docker, 'inspect', container_name], stdout=FNULL, stderr=FNULL) == 0:
    # Container exists already, compare images
    print('Existing container {} found, comparing revisions'.format(container_name))
    current_image = subprocess.check_output([bin_docker, 'inspect', '-f', '{{.Config.Image}}', container_name]).strip()
    if current_image != target_image:
        # Remove stale container
        print('Removing stale container based on image {}'.format(current_image))
        subprocess.call([bin_docker, 'stop', container_name])
        subprocess.call([bin_docker, 'rm', container_name])
    else:
        # Existing container is up-to-date
        if subprocess.check_output([bin_docker, 'ps', '-f', 'name={}'.format(container_name), '--format', '{{.Names}}']).strip() == container_name:
            # Existing container online, nothing left to do
            print('Container {} is up to date'.format(container_name))
        else:
            # Existing container is offline, start it
            print('Container {} is up to date, but offline - initiating startup'.format(container_name))
            subprocess.call([bin_docker, 'start', container_name], stdout=FNULL)
        exit(0)

# Launch a new container
print('Launching new container {} with image {}'.format(container_name, target_image))
call_args = [bin_docker,
                 'run',
                 '--name',
                 container_name,
                 '--privileged',
                 '--restart unless-stopped',
                 '-v',
                 '{}:/etc/manager'.format(os.path.dirname(config_archive)),
                 '-v',
                 '{}:/mnt'.format(update_path),
                 '-e',
                 'CONFIG_FILE={}'.format(os.path.basename(config_archive))]
if interface is not None:
    call_args.append('-e')
    call_args.append('INTERFACE={}'.format(interface))
if network is not None:
    call_args.append('--net')
    call_args.append(network)
if attach is not None:
    call_args.append('-ti')
else:
    call_args.append('-d')
call_args.append(target_image)
subprocess.call(call_args)
