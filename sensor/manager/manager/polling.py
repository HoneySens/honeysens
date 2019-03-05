from __future__ import absolute_import

import binascii
import fcntl
import json
import os
import socket
import struct
import subprocess
import sys
import tarfile
import threading
import time
import traceback

from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import hashes
from cryptography import x509

from . import hooks
from . import state
from .utils import communication
from .utils import constants


_timer = None
_config_dir = None
_config = None
_config_archive = None
_interface = None
_platform = None


def worker():
    global _timer
    # Send status data to server
    try:
        print('POLLING: Performing polling process')
        hooks.execute_hook(constants.Hooks.ON_BEFORE_POLL, [_config, _config_dir])
        sys.stdout.flush()
        r = send_data(collect_data())
        result = json.loads(r['content'])
        network_changed = update_config(result)
        try:
            state.apply_config(_config, result, network_changed)
            hooks.execute_hook(constants.Hooks.ON_POLL, [result])
        except Exception as e:
            print('Warning: Exception when trying to apply new configuration ({})'.format(str(e)))
            # traceback.print_exc()
        next_execution = _config.getint('server', 'interval') * 60
    except Exception as e:
        # traceback.print_exc()
        print('Warning: Polling failed, retrying in 60 seconds ({})'.format(str(e)))
        hooks.execute_hook(constants.Hooks.ON_POLL_ERROR)
        # Retry in one minute if something fails (server unreachable, etc.)
        next_execution = 60

    # Reschedule worker
    _timer = threading.Timer(next_execution, worker, args=())
    _timer.setDaemon(True)
    _timer.start()


def get_ip_address(iface):
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    return socket.inet_ntoa(fcntl.ioctl(s.fileno(), 0x8915, struct.pack(b'256s', iface[:15].encode('utf-8')))[20:24])


def collect_data():
    # Client certificate fingerprinting
    with open('{}/{}'.format(_config_dir, _config.get('general', 'certfile')), 'r') as f:
        sensor_crt_src = f.read()
    sensor_crt = x509.load_pem_x509_certificate(sensor_crt_src, default_backend())
    sensor_crt_fp = binascii.hexlify(sensor_crt.fingerprint(hashes.SHA256()))
    # Server certificate fingerprinting
    with open('{}/{}'.format(_config_dir, _config.get('server', 'certfile')), 'r') as f:
        server_crt_src = f.read()
    server_crt = x509.load_pem_x509_certificate(server_crt_src, default_backend())
    server_crt_fp = binascii.hexlify(server_crt.fingerprint(hashes.SHA256()))
    # Analyze RAM usage
    p = subprocess.Popen(['free', '-m'], stdout=subprocess.PIPE)
    out, err = p.communicate()
    free_mem = out.decode('utf-8').split('\n')[2].split()[3]
    status_code = 1
    # Get disk usage for / in MB
    st = os.statvfs('/')
    disk_total = st.f_blocks * st.f_frsize / 1024 / 1024
    disk_usage = (st.f_blocks - st.f_bfree) * st.f_frsize / 1024 / 1024
    #if update_running:
        #status_code = 2
    return {'timestamp': int(time.time()),
            'status': status_code,
            'crt_fp': sensor_crt_fp,
            'srv_crt_fp': server_crt_fp,
            'ip': get_ip_address(_interface),
            'free_mem': free_mem,
            'disk_usage': disk_usage,
            'disk_total': disk_total,
            'sw_version': _platform.get_current_revision()}


def send_data(data):
    signing_key = open('{}/{}'.format(_config_dir, _config.get('general', 'keyfile')), 'r').read()
    crt_fp = data['crt_fp']
    srv_crt_fp = data['srv_crt_fp']
    del data['crt_fp'], data['srv_crt_fp']
    post_data = {'sensor': _config.get('general', 'sensor_id'), 'crt_fp': crt_fp, 'srv_crt_fp': srv_crt_fp,
                 'status': communication.encode_data(json.dumps(data).encode('ascii')),
                 'signature': communication.sign_data(signing_key, data)}
    return communication.perform_https_request(_config, _config_dir, 'api/sensors/status', communication.REQUEST_TYPE_POST, post_data=post_data)


def update_config(config_data):
    network_changed = False
    if 'server_endpoint_host' in config_data and str(config_data['server_endpoint_host']) != _config.get('server', 'host'):
        _config.set('server', 'host', str(config_data['server_endpoint_host']))
        network_changed = True
    if 'server_endpoint_port_https' in config_data:
        _config.set('server', 'port_https', str(config_data['server_endpoint_port_https']))
    if 'update_interval' in config_data:
        _config.set('server', 'interval', str(config_data['update_interval']))
    if 'network_ip_mode' in config_data and str(config_data['network_ip_mode']) != _config.get('network', 'mode'):
        _config.set('network', 'mode', str(config_data['network_ip_mode']))
        network_changed = True
    if 'network_ip_address' in config_data and str(config_data['network_ip_address']) != _config.get('network', 'address'):
        _config.set('network', 'address', str(config_data['network_ip_address']))
        network_changed = True
    if 'network_ip_netmask' in config_data and str(config_data['network_ip_netmask']) != _config.get('network', 'netmask'):
        _config.set('network', 'netmask', str(config_data['network_ip_netmask']))
        network_changed = True
    if 'network_ip_gateway' in config_data and str(config_data['network_ip_gateway']) != _config.get('network', 'gateway'):
        _config.set('network', 'gateway', str(config_data['network_ip_gateway']))
        network_changed = True
    if 'network_ip_dns' in config_data and str(config_data['network_ip_dns']) != _config.get('network', 'dns'):
        _config.set('network', 'dns', str(config_data['network_ip_dns']))
        network_changed = True
    if 'network_mac_mode' in config_data and str(config_data['network_mac_mode']) != _config.get('mac', 'mode'):
        _config.set('mac', 'mode', str(config_data['network_mac_mode']))
        network_changed = True
    if 'network_mac_address' in config_data and str(config_data['network_mac_address']) != _config.get('mac', 'address'):
        _config.set('mac', 'address', str(config_data['network_mac_address']))
        network_changed = True
    if 'proxy_mode' in config_data and str(config_data['proxy_mode']) != _config.get('proxy', 'mode'):
        _config.set('proxy', 'mode', str(config_data['proxy_mode']))
        network_changed = True
    if 'proxy_host' in config_data and str(config_data['proxy_host']) != _config.get('proxy', 'host'):
        _config.set('proxy', 'host', str(config_data['proxy_host']))
        network_changed = True
    if 'proxy_port' in config_data and str(config_data['proxy_port']) != _config.get('proxy', 'port'):
        _config.set('proxy', 'port', str(config_data['proxy_port']))
        network_changed = True
    if 'proxy_user' in config_data and str(config_data['proxy_user']) != _config.get('proxy', 'user'):
        _config.set('proxy', 'user', str(config_data['proxy_user']))
        network_changed = True
    if 'proxy_password' in config_data and str(config_data['proxy_password']) != _config.get('proxy', 'password'):
        _config.set('proxy', 'password', str(config_data['proxy_password']))
        network_changed = True
    # Save new config
    with open('{}/honeysens.cfg'.format(_config_dir), 'w') as f:
        _config.write(f)
    # Client certificate update
    if 'sensor_crt' in config_data:
        print('New client certificate received, saving to disk')
        with open('{}/{}'.format(_config_dir, _config.get('general', 'certfile')), 'w') as f:
            f.write(str(config_data['sensor_crt']))
    # Server certificate update
    if 'server_crt' in config_data:
        print('New server certificate received, saving to disk')
        with open('{}/{}'.format(_config_dir, _config.get('server', 'certfile')), 'w') as f:
            f.write(str(config_data['server_crt']))
    # Rewrite config archive
    # TODO Track if a config option was changed and only do that when necessary
    with tarfile.open(_config_archive, 'w:gz') as config_archive:
        for f in os.listdir(_config_dir):
            config_archive.add('{}/{}'.format(_config_dir, f), f)
    return network_changed


def start(config_dir, config, config_archive, interface, platform):
    global _config_dir, _config, _config_archive, _interface, _platform
    print('Starting polling worker')
    _config_dir = config_dir
    _config = config
    _config_archive = config_archive
    _interface = interface
    _platform = platform
    worker()


def stop():
    if _timer is not None:
        print('POLLING: Stopping worker')
        _timer.cancel()
