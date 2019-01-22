#!/usr/bin/env python2

import sys
import os

if not len(sys.argv) == 2:
    print('Usage: clear_data.py <data-dir>')
    exit()

data_dir = os.path.abspath(sys.argv[1])
if not os.path.isdir(data_dir):
    print('Error: Provided path is not a directory')
    exit(1)

config_dir = '{}/configs'.format(data_dir)
firmware_dir = '{}/firmware'.format(data_dir)
firmware_sd_dir = '{}/firmware/sd'.format(data_dir)
upload_dir = '{}/upload'.format(data_dir)

if not os.path.isdir(config_dir) or not os.path.isdir(firmware_dir) or not os.path.isdir(
        firmware_sd_dir) or not os.path.isdir(upload_dir):
    print('Error: Invalid directory layout')
    exit(2)

to_remove = []
for work_dir in [config_dir, firmware_dir, firmware_sd_dir, upload_dir]:
    for entry in os.listdir(work_dir):
        path = '{}/{}'.format(work_dir, entry)
        if os.path.isfile(path):
            to_remove.append(path)

for candidate in to_remove:
    print('Removing {}'.format(candidate))
    os.remove(candidate)
