from __future__ import absolute_import

import re

from . import hooks
from . import services
from .utils import constants


def is_ip(value):
    parts = value.split('.')
    if len(parts) == 4 and all([x.isdigit() for x in parts]):
        numbers = list(int(x) for x in parts)
        return all([num >= 0 and num <= 255 for num in numbers])
    return False


def update_server_endpoint(host, name):
    print('Updating server endpoint in /etc/hosts')
    with open('/etc/hosts', 'rb') as f:
        hosts_content = f.read()
    if 'honeysens-server' in hosts_content:
        # Adjust existing hosts entry
        # TODO Only write if changes happened

        # We can't use the fileinput module here because that one physically moves file which is not possible
        # with /etc/hosts inside docker containers (it's a mounted file)
        hosts_content = re.sub('\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}\t\S+ honeysens-server', '{}\t{} honeysens-server'.format(host, name), hosts_content)
        with open('/etc/hosts', 'wb') as f:
            f.write(hosts_content)
    else:
        # Add new hosts entry
        with open('/etc/hosts', 'a') as f:
            f.write('{}\t{} {}\n'.format(host, name, 'honeysens-server'))


def apply_config(config, server_response, reset_network=False):
    if reset_network:
        # Stop services
        services.stop_all()
        # Adjust /etc/hosts if necessary
        server_host = config.get('server', 'host')
        server_name = config.get('server', 'name')
        if is_ip(server_host):
            update_server_endpoint(server_host, server_name)
    hooks.execute_hook(constants.Hooks.ON_APPLY_CONFIG, [config, server_response, reset_network])
