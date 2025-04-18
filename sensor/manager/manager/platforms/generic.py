from debinterface import interfaces
import glob
import logging
import os
import shutil
import subprocess

from manager.utils import constants


class GenericPlatform(object):

    cntlm_cfg_path = '/etc/cntlm.conf'
    config_dir = None
    logger = None
    services_network_name = None
    firmware_update_in_progress = False
    service_update_in_progress = False

    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        self.logger = logging.getLogger(__name__)
        self.config_dir = config_dir

    def get_architecture(self):
        return None

    def enable_docker(self, force_restart):
        pass

    def set_services_network_iface(self, name):
        if name != self.services_network_name:
            self.logger.info('Registering services interface {}'.format(name))
            self.services_network_name = name

    def get_services_network_iface(self):
        return self.services_network_name

    def generate_services_network_iface(self):
        return 'services'

    def set_firmware_update_in_progress(self, state):
        self.firmware_update_in_progress = state

    def is_firmware_update_in_progress(self):
        return self.firmware_update_in_progress

    def set_service_update_in_progress(self, state):
        self.service_update_in_progress = state

    def is_service_update_in_progress(self):
        return self.service_update_in_progress

    def update_iface_configuration(self, iface, mode, address=None, netmask=None, gateway=None, dns=None, eapol=None):
        ifaces = interfaces.Interfaces()
        # Verify network interface presence
        if ifaces.getAdapter(iface) is None:
            ifaces.addAdapter(iface, 0)
        adapter = ifaces.getAdapter(iface)
        adapter.setAddrFam('inet')
        # Configure interface details
        if mode == '0': # DHCP
            adapter.setAddressSource('dhcp')
            adapter.setAddress(None)
            adapter.setNetmask(None)
            adapter.setGateway(None)
            if eapol is not None:
                adapter.setUnknown('wpa-driver', 'wired')
                adapter.setWpaConf(eapol)
            else:
                adapter.setUnknown('wpa-driver', None)
                # Workaround, because 'wpa-conf' can't be unset via the API
                if 'wpa-conf' in adapter._ifAttributes:
                    del adapter._ifAttributes['wpa-conf']
            # Workaround, because 'dns-nameservers' can't be unset via the API
            if 'dns-nameservers' in adapter._ifAttributes:
                del adapter._ifAttributes['dns-nameservers']
        elif mode == '1': # Static configuration
            adapter.setAddressSource('static')
            adapter.setAddress(address)
            adapter.setNetmask(netmask)
            if gateway:
                adapter.setGateway(gateway)
            else:
                adapter.setGateway(None)
            if dns:
                adapter.setUnknown('dns-nameservers', dns)
            else:
                # Workaround, because 'dns-nameservers' can't be unset via the API
                if 'dns-nameservers' in adapter._ifAttributes:
                    del adapter._ifAttributes['dns-nameservers']
            if eapol is not None:
                adapter.setUnknown('wpa-driver', 'wired')
                adapter.setWpaConf(eapol)
            else: # Unconfigured interface
                adapter.setUnknown('wpa-driver', None)
                # Workaround, because 'wpa-conf' can't be unset via the API
                if 'wpa-conf' in adapter._ifAttributes:
                    del adapter._ifAttributes['wpa-conf']
        elif mode == '2':
            ifaces.removeAdapterByName(iface)
        ifaces.writeInterfaces()

    def update_mac_address(self, iface, mac):
        self.logger.info('Changing MAC address of {} to {}'.format(iface, mac))
        subprocess.call(['/usr/bin/macchanger', '-m', mac, iface])

    def get_current_revision(self):
        revision = None
        if os.path.isfile(constants.REVISION_MARKER):
            with open(constants.REVISION_MARKER, 'r') as f:
                revision = f.read().strip()
        return revision

    def configure_eapol(self, conf_dir, conf_name, eap_mode, identity, password, ca_cert, anon_identity, client_cert, client_key, key_passphrase):
        # Translate eap_mode into a wpa_supplicant-compatible string
        eap_modes = {'1': 'MD5', '2': 'TLS', '3': 'PEAP', '4': 'TTLS'}
        # Remove potentially deprecated former config files, since those will be overwritten anyways
        old_certs = glob.glob('{}/*.crt'.format(conf_dir))
        old_keys = glob.glob('{}/*.key'.format(conf_dir))
        for old_file in old_certs + old_keys + ['{}/{}'.format(conf_dir, conf_name)]:
            if os.path.isfile(old_file):
                self.logger.debug('Removing {}'.format(old_file))
                os.remove(old_file)
        if eap_mode == '0':
            # EAP is disabled, we can quit here
            return
        # Creates a wpa_supplicant configuration with the supplied parameters and writes it to the given path
        config = ['ctrl_interface=/run/wpa_supplicant\n\n',
                  'network={\n',
                  ' key_mgmt=IEEE8021X\n',
                  ' eap={}\n'.format(eap_modes[eap_mode]),
                  ' identity="{}"\n'.format(identity)]
        # Evaluate supplied EAP mode
        if (eap_mode == '1' or eap_mode == '3' or eap_mode == '4') and password is not None:
            # MD5/PEAP/TTLS
            config.append(' password="{}"\n'.format(password))
        elif eap_mode == '2':
            # TLS
            self.logger.debug('Writing {} and {} to {}'.format(client_cert, client_key, conf_dir))
            shutil.copy('{}/{}'.format(self.config_dir, client_cert), conf_dir)
            shutil.copy('{}/{}'.format(self.config_dir, client_key), conf_dir)
            config.append(' client_cert="{}/{}"\n'.format(conf_dir, client_cert))
            config.append(' private_key="{}/{}"\n'.format(conf_dir, client_key))
        # Optional settings
        if ca_cert is not None:
            self.logger.debug('Writing {} to {}'.format(ca_cert, conf_dir))
            shutil.copy('{}/{}'.format(self.config_dir, ca_cert), conf_dir)
            config.append(' ca_cert="{}/{}"\n'.format(conf_dir, ca_cert))
        if anon_identity is not None:
            config.append(' anonymous_identity="{}"\n'.format(anon_identity))
        if key_passphrase is not None:
            config.append(' private_key_passwd="{}"\n'.format(key_passphrase))
        # Add footer
        config.append('}\n')
        # Write configuration
        with open('{}/{}'.format(conf_dir, conf_name), 'w') as f:
            f.writelines(config)

    def configure_cntlm(self, proxy, user, password):
        # Reconfigures the cntlm daemon for the given proxy settings by performing the following steps:
        # - Extracts the domain portion from the given user, if available
        # - Runs "cntlm -H" to determine password hashes for the given credentials
        # - Updates the configuration file with the result
        if user is not None:
            userdomain = user.split('\\')
            if len(userdomain) == 1:
                # No domain was given, assume default
                domain = 'default'
                username = userdomain[0]
            else:
                domain = userdomain[0]
                username = userdomain[1]
            p = subprocess.Popen(['cntlm', '-H', '-u', username, '-d', domain], stdin=subprocess.PIPE, stdout=subprocess.PIPE)
            cntlm_stdout, _ = p.communicate(input=password.encode('utf-8'))
            cntlm_cfg = cntlm_stdout.decode('utf-8').split('\n')
        else:
            cntlm_cfg = ['']
            domain = ''
            username = ''
        # Fill the list with the remaining cntlm config options
        cntlm_cfg[0] = 'Listen 3128'  # First stdout line is always empty
        cntlm_cfg.append('NoProxy localhost, 127.0.0.*, 10.*, 192.168.*')
        cntlm_cfg.append('Proxy {}'.format(proxy))
        cntlm_cfg.append('Username {}'.format(username))
        cntlm_cfg.append('Domain {}'.format(domain))
        cntlm_cfg.append('Password {}'.format(password))
        with open(self.cntlm_cfg_path, 'w') as f:
            for opt in cntlm_cfg:
                f.write(opt)
                f.write('\n')

    def cleanup(self):
        # Room for platforms to perform cleanup operations on manager shutdown
        pass
