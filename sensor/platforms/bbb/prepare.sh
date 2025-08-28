#!/usr/bin/env bash
set -ex

# Skip interactive apt dialogues
export DEBIAN_FRONTEND=noninteractive

# Update packet repository
apt update

# Basic dependencies
apt -y install macchanger networkd-dispatcher resolvconf screen tcpdump wpasupplicant

# Install Docker CE (follows https://docs.docker.com/engine/installation/linux/docker-ce/debian)
apt -y install apt-transport-https curl gnupg-agent software-properties-common
curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add -
add-apt-repository -y "deb [arch=armhf] https://download.docker.com/linux/debian $(lsb_release -cs) stable"
apt update
apt install -y --no-install-suggests docker-ce

# Build sensor manager
apt install -y iptables python3-cryptography python3-netifaces python3-pip python3-requests python3-venv python3-zmq libcurl4-gnutls-dev libgnutls28-dev cntlm libyaml-dev
mkdir /etc/manager
python3 -m venv --system-site-packages /opt/manager/venv
/opt/manager/venv/bin/pip3 install /opt/manager

# Register sensor manager service
ln -s /etc/systemd/system/manager.service /etc/systemd/system/multi-user.target.wants/manager.service

# Expose sensor manager CLI
ln -s /opt/manager/venv/bin/manager-cli /usr/local/bin/manager-cli

# Disable IPv6
echo "net.ipv6.conf.all.disable_ipv6 = 1" > /etc/sysctl.d/70-disable-ipv6.conf

# Run sshd only while the Ethernet-over-USB gadget is active
sed -i 's/#ListenAddress 0.0.0.0/ListenAddress 192.168.7.2/g' /etc/ssh/sshd_config
sed -i 's/#ListenAddress ::/ListenAddress 192.168.6.2/g' /etc/ssh/sshd_config
sed -i 's/#Port 22/Port 22222/g' /etc/ssh/sshd_config
systemctl disable ssh
mv -v /root/usb_gadget_down.sh /etc/networkd-dispatcher/no-carrier.d/00usb-gadget
mv -v /root/usb_gadget_up.sh /etc/networkd-dispatcher/routable.d/00usb-gadget

# Disable systemd's NTP, because it interferes with HTTPS time sync
systemctl disable systemd-timesyncd

# Disable the default wpa_supplicant D-Bus service, since we launch our own instances via ifup/ifdown
systemctl disable wpa_supplicant

# Disable BBB sysconf
systemctl disable bbbio-set-sysconf

# Disable unattended-upgrades
sed -i -e 's/APT::Periodic::Update-Package-Lists "1";/APT::Periodic::Update-Package-Lists "0";/g' -e 's/APT::Periodic::Unattended-Upgrade "1";/APT::Periodic::Unattended-Upgrade "0";/g' /etc/apt/apt.conf.d/20auto-upgrades
systemctl disable apt-daily.timer
systemctl disable apt-daily.service
systemctl disable apt-daily-upgrade.timer
systemctl disable apt-daily-upgrade.service

# Regenerate /etc/resolv.conf
rm -v /etc/resolv.conf
dpkg-reconfigure resolvconf

# Limit journald log size
sed -i 's/#SystemMaxUse.*/SystemMaxUse=100M/g' /etc/systemd/journald.conf

# Clean up to save space
apt remove -y --purge avahi-daemon bb-bbai-firmware bb-wl18xx-firmware build-essential docker-buildx-plugin docker-compose-plugin dpkg-dev firmware-atheros firmware-brcm80211 firmware-iwlwifi firmware-libertas firmware-realtek firmware-ti-connectivity gcc-12 g++-12 git iwd libyaml-dev libcurl4-gnutls-dev locales mender-client nginx nginx-common 
apt autoremove -y
apt clean
rm -rf /var/lib/apt/lists/*
rm -rf /var/log/{auth.log,syslog,cron.log,daemon.log,kern.log,lpr.log,mail.log,mail.info,mail.warn,mail.err,user.log,debug,messages,alternatives.log,bootstrap.log,dpkg.log}
rm -rf /usr/share/locale

# Revision marker
echo $1 > /revision
