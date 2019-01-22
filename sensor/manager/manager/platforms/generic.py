from __future__ import absolute_import

from debinterface import interfaces
import os
import subprocess

from manager.utils import constants


class GenericPlatform:

    cntlm_cfg_path = '/etc/cntlm.conf'

    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        pass

    def get_architecture(self):
        return None

    def enable_docker(self, force_restart):
        pass

    def update_iface_configuration(self, iface, mode, address=None, netmask=None, gateway=None, dns=None):
        ifaces = interfaces.Interfaces()
        # Verify network interface presence
        if ifaces.getAdapter(iface) is None:
            ifaces.addAdapter(iface, 0)
        adapter = ifaces.getAdapter(iface)
        adapter.setAddrFam('inet')
        # Configure interface details
        if mode == '0':
            adapter.setAddressSource('dhcp')
            adapter.setAddress(None)
            adapter.setNetmask(None)
            adapter.setGateway(None)
            # Debinterfaces is missing the option to remove 'unknown' attributes, therefore we need to improvise
            if 'unknown' in adapter._ifAttributes:
                del (adapter._ifAttributes['unknown'])
        elif mode == '1':
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
                # Debinterfaces is missing the option to remove 'unknown' attributes, therefore we need to improvise
                if 'unknown' in adapter._ifAttributes:
                    del (adapter._ifAttributes['unknown'])
        elif mode == '2':
            ifaces.removeAdapterByName(iface)
        ifaces.writeInterfaces()

    def update_mac_address(self, iface, mac):
        print('Changing MAC address of {} to {}'.format(iface, mac))
        subprocess.call(['/usr/bin/macchanger', '-m', mac, iface])

    def get_current_revision(self):
        revision = None
        if os.path.isfile(constants.REVISION_MARKER):
            with open(constants.REVISION_MARKER, 'r') as f:
                revision = f.read().strip()
        return revision

    def configure_cntlm(self, proxy, user, password):
        # Reconfigures the running cntlm daemon for the given proxy settings by performing the following steps:
        # - Extracts the domain portion from the given user, if available
        # - Runs "cntlm -H" to determine password hashes for the given credentials
        # - Updates the configuration file with the result
        # TODO Don't hardcore a string like 'None', use real empty values
        # (requires to rewrite the way polling.py writes config values)
        if user != 'None':
            userdomain = user.split('\\')
            if len(userdomain) == 1:
                # No domain was given, assume default
                domain = 'default'
                username = userdomain[0]
            else:
                domain = userdomain[0]
                username = userdomain[1]
            p = subprocess.Popen(['cntlm', '-H', '-u', username, '-d', domain], stdin=subprocess.PIPE, stdout=subprocess.PIPE)
            cntlm_cfg = p.communicate(input=b'{}'.format(password))[0].split('\n')
        else:
            cntlm_cfg = ['']
            domain = ''
            username = ''
        # Fill the list with the remaining cntlm config options
        cntlm_cfg[0] = 'Listen 3128'
        cntlm_cfg.append('NoProxy localhost, 127.0.0.*, 10.*, 192.168.*')
        cntlm_cfg.append('Proxy {}'.format(proxy))
        cntlm_cfg.append('Username {}'.format(username))
        cntlm_cfg.append('Domain {}'.format(domain))
        cntlm_cfg.append('Password {}'.format(password))
        with open(self.cntlm_cfg_path, 'w') as f:
            for opt in cntlm_cfg:
                f.write(opt)
                f.write('\n')
