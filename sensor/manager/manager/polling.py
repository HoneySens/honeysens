import binascii
import fcntl
import json
import logging
import os
import socket
import struct
import subprocess
import sys
import tarfile
import threading
import time
#import traceback

from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import hashes
from cryptography import x509

from . import hooks
from manager import services
from .utils import communication
from .utils import constants

_config = None
_config_archive = None
_config_dir = None
_first_poll = True
_interface = None
_last_server_response = {}
_last_successful_poll = None
_logger = None
_platform = None
_state_queue = None
_timer = None


def worker():
    global _timer, _first_poll, _last_successful_poll, _last_server_response
    result = {}
    # Send status data to server
    try:
        _logger.info('Performing polling process')
        hooks.execute_hook(constants.Hooks.ON_BEFORE_POLL, [_config, _config_dir])
        sys.stdout.flush()
        r = send_data(collect_data())
        result = json.loads(r['content'])
        network_changed = update_config(result)
        _last_successful_poll = int(time.time())
        _last_server_response = result
        try:
            _logger.debug('Enqueuing state configuration')
            _state_queue.put({'config': _config, 'server_response': result, 'network_changed': network_changed})
            _first_poll = False
        except Exception as e:
            _logger.error('Exception when trying to apply new configuration ({})'.format(str(e)))
            # traceback.print_exc()
        next_execution = _config.getint('server', 'interval') * 60
    except Exception as e:
        # traceback.print_exc()
        _logger.warning('Polling failed, retrying in 60 seconds ({})'.format(str(e)))
        trigger_conn_error()
        # Retry in one minute if something fails (server unreachable, etc.)
        next_execution = 60

    # Execute hooks regardless of polling result
    hooks.execute_hook(constants.Hooks.ON_POLL)
    # Reschedule worker
    _timer = threading.Timer(next_execution, worker, args=())
    _timer.setDaemon(True)
    _timer.start()


def get_ip_address(iface):
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    return socket.inet_ntoa(fcntl.ioctl(s.fileno(), 0x8915, struct.pack(b'256s', iface[:15].encode('utf-8')))[20:24])


def get_certificate_fp(path):
    if path is not None and os.path.isfile(path):
        try:
            with open(path, 'rb') as f:
                crt_src = f.read()
            crt = x509.load_pem_x509_certificate(crt_src, default_backend())
            return binascii.hexlify(crt.fingerprint(hashes.SHA256())).decode('ascii')
        except Exception:
            return None
    return None


def collect_data():
    # Collect certificate fingerprints
    server_crt_fp = get_certificate_fp('{}/{}'.format(_config_dir, _config.get('server', 'certfile')))
    eapol_ca_cert_fp = get_certificate_fp('{}/{}'.format(_config_dir, _config.get('eapol', 'ca_cert')))
    eapol_client_cert_fp = get_certificate_fp('{}/{}'.format(_config_dir, _config.get('eapol', 'client_cert')))
    # Sensor status
    if _platform.is_firmware_update_in_progress():
        status = constants.SensorStatus.UPDATING
    else:
        status = constants.SensorStatus.RUNNING
    # Analyze RAM usage
    p = subprocess.Popen(['free', '-m'], stdout=subprocess.PIPE)
    out, err = p.communicate()
    free_mem = out.decode('utf-8').split('\n')[2].split()[3]
    # Get disk usage for / in MB
    st = os.statvfs('/')
    disk_total = st.f_blocks * st.f_frsize / 1024 / 1024
    disk_usage = (st.f_blocks - st.f_bfree) * st.f_frsize / 1024 / 1024
    # Service status: Don't return service status for the very first poll, because services aren't started yet
    if _first_poll:
        service_status = {}
    else:
        service_status = services.get_status()
    return {'disk_total': int(disk_total),
            'disk_usage': int(disk_usage),
            'eapol_ca_crt_fp': eapol_ca_cert_fp,
            'eapol_client_crt_fp': eapol_client_cert_fp,
            'free_mem': free_mem,
            'ip': get_ip_address(_interface),
            'service_status': service_status,
            'srv_crt_fp': server_crt_fp,
            'status': status,
            'sw_version': _platform.get_current_revision(),
            'timestamp': int(time.time())}


def send_data(data):
    srv_crt_fp = data['srv_crt_fp']
    eapol_ca_crt_fp = data['eapol_ca_crt_fp']
    eapol_client_crt_fp = data['eapol_client_crt_fp']
    del data['srv_crt_fp'], data['eapol_ca_crt_fp'], data['eapol_client_crt_fp']
    post_data = {'sensor': _config.get('general', 'sensor_id'),
                 'srv_crt_fp': srv_crt_fp,
                 'eapol_ca_crt_fp': eapol_ca_crt_fp,
                 'eapol_client_crt_fp': eapol_client_crt_fp,
                 'status': communication.encode_data(json.dumps(data).encode('ascii'))}
    if _config.get('general', 'secret') is None:
        # Request secret in case we haven't received ours yet
        post_data['req_secret'] = True
    return communication.perform_https_request(_config, _config_dir, 'api/sensors/status', communication.REQUEST_TYPE_POST, post_data=post_data)


def update_config_param(section, param, updated_params, updated_key, trigger_network_change=False):
    if updated_key in updated_params:
        if updated_params[updated_key] is None and _config.get(section, param) is not None:
            _config.set(section, param, None)
            return trigger_network_change
        elif str(updated_params[updated_key]) != str(_config.get(section, param)):
            _config.set(section, param, str(updated_params[updated_key]))
            return trigger_network_change
    return False


def update_config(config_data):
    network_changed = False or update_config_param('server', 'host', config_data, 'server_endpoint_host', True)
    network_changed |= update_config_param('server', 'port_https', config_data, 'server_endpoint_port_https')
    network_changed |= update_config_param('server', 'interval', config_data, 'update_interval')
    network_changed |= update_config_param('general', 'secret', config_data, 'secret')
    network_changed |= update_config_param('general', 'service_network', config_data, 'service_network')
    network_changed |= update_config_param('network', 'mode', config_data, 'network_ip_mode', True)
    network_changed |= update_config_param('network', 'address', config_data, 'network_ip_address', True)
    network_changed |= update_config_param('network', 'netmask', config_data, 'network_ip_netmask', True)
    network_changed |= update_config_param('network', 'gateway', config_data, 'network_ip_gateway', True)
    network_changed |= update_config_param('network', 'dns', config_data, 'network_ip_dns', True)
    network_changed |= update_config_param('network', 'dhcp_hostname', config_data, 'network_dhcp_hostname', True)
    network_changed |= update_config_param('eapol', 'mode', config_data, 'eapol_mode', True)
    network_changed |= update_config_param('eapol', 'identity', config_data, 'eapol_identity', True)
    network_changed |= update_config_param('eapol', 'password', config_data, 'eapol_password', True)
    network_changed |= update_config_param('eapol', 'anon_identity', config_data, 'eapol_anon_identity', True)
    network_changed |= update_config_param('eapol', 'client_key_password', config_data, 'eapol_client_key_password', True)
    network_changed |= update_config_param('mac', 'mode', config_data, 'network_mac_mode', True)
    network_changed |= update_config_param('mac', 'address', config_data, 'network_mac_address', True)
    network_changed |= update_config_param('proxy', 'mode', config_data, 'proxy_mode', True)
    network_changed |= update_config_param('proxy', 'host', config_data, 'proxy_host', True)
    network_changed |= update_config_param('proxy', 'port', config_data, 'proxy_port', True)
    network_changed |= update_config_param('proxy', 'user', config_data, 'proxy_user', True)
    network_changed |= update_config_param('proxy', 'password', config_data, 'proxy_password', True)
    # Server certificate update
    if 'server_crt' in config_data:
        _logger.info('New server certificate received, saving to disk')
        with open('{}/{}'.format(_config_dir, _config.get('server', 'certfile')), 'w') as f:
            f.write(str(config_data['server_crt']))
    # EAPOL CA certificate update
    if 'eapol_ca_cert' in config_data:
        network_changed = True
        if config_data['eapol_ca_cert'] is None:
            ca_cert_path = '{}/{}'.format(_config_dir, _config.get('eapol', 'ca_cert'))
            if ca_cert_path is not None and os.path.isfile(ca_cert_path):
                _logger.info('Removing local EAPOL CA certificate')
                os.remove(ca_cert_path)
            _config.set('eapol', 'ca_cert', None)
        else:
            _logger.info('New EAPOL CA certificate received, saving to disk')
            ca_cert_path = '{}/eapol_ca.crt'.format(_config_dir)
            with open(ca_cert_path, 'w') as f:
                f.write(str(config_data['eapol_ca_cert']))
            _config.set('eapol', 'ca_cert', 'eapol_ca.crt')
    # EAPOL Client certificate and key update
    if 'eapol_client_cert' in config_data and 'eapol_client_key' in config_data:
        network_changed = True
        if config_data['eapol_client_cert'] is None:
            client_cert_path = '{}/{}'.format(_config_dir, _config.get('eapol', 'client_cert'))
            client_key_path = '{}/{}'.format(_config_dir, _config.get('eapol', 'client_key'))
            if client_cert_path is not None and os.path.isfile(client_cert_path):
                _logger.info('Removing local EAPOL TLS client certificate')
                os.remove(client_cert_path)
            _config.set('eapol', 'client_cert', None)
            if client_key_path is not None and os.path.isfile(client_key_path):
                _logger.info('Removing local EAPOL TLS client key')
                os.remove(client_key_path)
            _config.set('eapol', 'client_key', None)
        else:
            _logger.info('New EAPOL TLS certificate/key received, saving to disk')
            client_cert_path = '{}/eapol_client.crt'.format(_config_dir)
            client_key_path = '{}/eapol_client.key'.format(_config_dir)
            with open(client_cert_path, 'w') as f:
                f.write(str(config_data['eapol_client_cert']))
            with open(client_key_path, 'w') as f:
                f.write(str(config_data['eapol_client_key']))
            _config.set('eapol', 'client_cert', 'eapol_client.crt')
            _config.set('eapol', 'client_key', 'eapol_client.key')
    # Save new config
    with open('{}/honeysens.cfg'.format(_config_dir), 'w') as f:
        _config.write(f)
    # Rewrite config archive
    # TODO Track if a config option was changed and only do that when necessary
    with tarfile.open(_config_archive, 'w:gz') as config_archive:
        for f in os.listdir(_config_dir):
            config_archive.add('{}/{}'.format(_config_dir, f), f)
    return network_changed


def is_online():
    if _last_successful_poll is None:
        return False
    interval = _config.getint('server', 'interval') * 60
    return (int(time.time()) - _last_successful_poll) <= interval


def trigger_conn_error():
    global _last_successful_poll
    _last_successful_poll = None
    hooks.execute_hook(constants.Hooks.ON_CONN_ERROR)


def get_last_server_response():
    return _last_server_response


def start(config_dir, config, config_archive, interface, platform, state_queue):
    global _config_dir, _config, _config_archive, _interface, _platform, _logger, _state_queue
    _logger = logging.getLogger(__name__)
    _logger.info('Starting polling worker')
    _config_dir = config_dir
    _config = config
    _config_archive = config_archive
    _interface = interface
    _platform = platform
    _state_queue = state_queue
    worker()


def stop():
    if _timer is not None:
        _logger.info('Stopping worker')
        _timer.cancel()
