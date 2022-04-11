import configparser
import json
import os
import shutil
import tarfile

from .. import constants
from .handler import HandlerInterface


class SensorConfigCreator(HandlerInterface):

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        job_params = json.loads(job_data['params'])
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        cursor = db.cursor()
        cursor.execute('SELECT * FROM sensors WHERE id = {}'.format(job_params['id']))
        sensor = cursor.fetchone()
        cursor.close()
        if sensor is None:
            raise Exception('Sensor {} not found in database'.format(job_params['id']))
        logger.info('Generating sensor configuration for sensor {}'.format(job_params['id']))
        logger.debug('Working directory: {}'.format(working_dir))
        logger.debug('Writing sensor certificate to {}/cert.pem'.format(working_dir))
        with open('{}/cert.pem'.format(working_dir), 'w') as f:
            f.write(job_params['cert'])
        logger.debug('Writing sensor key to {}/key.pem'.format(working_dir))
        with open('{}/key.pem'.format(working_dir), 'w') as f:
            f.write(job_params['key'])
        logger.debug('Copying server certificate bundle: {} -> {}/server-cert.pem'.format(constants.TLS_CERT_PATH, working_dir))
        shutil.copy(constants.TLS_CERT_PATH, '{}/server-cert.pem'.format(working_dir))
        if job_params['eapol_ca_cert'] is not None:
            eapol_ca_crt_path = 'eapol_ca.crt'
            logger.debug('Writing EAPOL CA certificate to {}/{}'.format(working_dir, eapol_ca_crt_path))
            with open('{}/{}'.format(working_dir, eapol_ca_crt_path), 'w') as f:
                f.write(job_params['eapol_ca_cert'])
            job_params['eapol_ca_cert'] = eapol_ca_crt_path
        if job_params['eapol_client_cert'] is not None:
            eapol_client_crt_path = 'eapol_client.crt'
            eapol_client_key_path = 'eapol_client.key'
            logger.debug('Writing EAPOL Client certificate to {}/{}'.format(working_dir, eapol_client_crt_path))
            with open('{}/{}'.format(working_dir, eapol_client_crt_path), 'w') as f:
                f.write(job_params['eapol_client_cert'])
            logger.debug('Writing EAPOL Client key to {}/{}'.format(working_dir, eapol_client_key_path))
            with open('{}/{}'.format(working_dir, eapol_client_key_path), 'w') as f:
                f.write(job_params['eapol_client_key'])
            job_params['eapol_client_cert'] = eapol_client_crt_path
            job_params['eapol_client_key'] = eapol_client_key_path
        logger.debug('Writing honeysens.cfg')
        sensor_config = configparser.ConfigParser(allow_no_value=True)
        sensor_config.add_section('server')
        sensor_config.add_section('general')
        sensor_config.add_section('network')
        sensor_config.add_section('mac')
        sensor_config.add_section('proxy')
        sensor_config.add_section('eapol')
        sensor_config.set('server', 'host', job_params['server_endpoint_host'])
        sensor_config.set('server', 'name', job_params['server_endpoint_name'])
        sensor_config.set('server', 'port_https', str(job_params['server_endpoint_port_https']))
        sensor_config.set('server', 'interval', '1')  # Use an update interval of one minute to quickly connect to the server
        sensor_config.set('server', 'certfile', 'server-cert.pem')
        sensor_config.set('general', 'sensor_id', str(job_params['id']))
        sensor_config.set('general', 'hostname', job_params['hostname'])
        sensor_config.set('general', 'secret', job_params['secret'])
        sensor_config.set('general', 'certfile', 'cert.pem')
        sensor_config.set('general', 'keyfile', 'key.pem')
        sensor_config.set('general', 'service_network', job_params['service_network'])
        sensor_config.set('network', 'mode', str(job_params['network_ip_mode']))
        sensor_config.set('network', 'address', job_params['network_ip_address'])
        sensor_config.set('network', 'netmask', job_params['network_ip_netmask'])
        sensor_config.set('network', 'gateway', job_params['network_ip_gateway'])
        sensor_config.set('network', 'dns', job_params['network_ip_dns'])
        sensor_config.set('network', 'dhcp_hostname', job_params['network_dhcp_hostname'])
        sensor_config.set('mac', 'mode', str(job_params['network_mac_mode']))
        sensor_config.set('mac', 'address', job_params['network_mac_address'])
        sensor_config.set('proxy', 'mode', str(job_params['proxy_mode']))
        sensor_config.set('proxy', 'host', job_params['proxy_host'])
        sensor_config.set('proxy', 'port', str(job_params['proxy_port']))
        sensor_config.set('proxy', 'user', job_params['proxy_user'])
        sensor_config.set('proxy', 'password', job_params['proxy_password'])
        sensor_config.set('eapol', 'mode', str(job_params['eapol_mode']))
        sensor_config.set('eapol', 'identity', job_params['eapol_identity'])
        sensor_config.set('eapol', 'password', job_params['eapol_password'])
        sensor_config.set('eapol', 'anon_identity', job_params['eapol_anon_identity'])
        sensor_config.set('eapol', 'ca_cert', job_params['eapol_ca_cert'])
        sensor_config.set('eapol', 'client_cert', job_params['eapol_client_cert'])
        sensor_config.set('eapol', 'client_key', job_params['eapol_client_key'])
        sensor_config.set('eapol', 'client_key_password', job_params['eapol_client_key_password'])
        with open('{}/honeysens.cfg'.format(working_dir), 'w') as sensor_config_file:
            sensor_config.write(sensor_config_file)
        result_dir = '{}/{}'.format(storage_path, job_data['id'])
        if os.path.isdir(result_dir):
            logger.debug('Result directory exists, removing it')
            shutil.rmtree(result_dir)
        logger.debug('Creating result directory {}'.format(result_dir))
        os.makedirs(result_dir)
        logger.debug('Packaging configuration archive into {}/{}.tar.gz'.format(result_dir, job_params['hostname']))
        files = ['cert.pem', 'key.pem', 'server-cert.pem', 'honeysens.cfg']
        if job_params['eapol_ca_cert'] is not None:
            files.append(job_params['eapol_ca_cert'])
        if job_params['eapol_client_cert'] is not None:
            files.append(job_params['eapol_client_cert'])
            files.append(job_params['eapol_client_key'])
        with tarfile.open('{}/{}.tar.gz'.format(result_dir, job_params['hostname']), 'w:gz') as config_archive:
            for name in files:
                config_archive.add('{}/{}'.format(working_dir, name), name)
        logger.debug('Cleaning up working directory {}'.format(working_dir))
        result_filename = '{}.tar.gz'.format(job_params['hostname'])
        # 'path' is relative to the base result directory
        return {'path': result_filename}
