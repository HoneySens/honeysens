#!/usr/bin/env sh
set -e

# Skip interactive apt dialogues
export DEBIAN_FRONTEND=noninteractive

# Update packet repository
apt update

# Basic dependencies
apt -y install macchanger resolvconf tcpdump wpasupplicant

# Install Docker CE (follows https://docs.docker.com/engine/installation/linux/docker-ce/debian)
apt -y install apt-transport-https curl gnupg-agent software-properties-common
curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add -
add-apt-repository "deb [arch=armhf] https://download.docker.com/linux/debian $(lsb_release -cs) stable"
apt update
apt install -y docker-ce

# Build sensor manager
apt install -y python3-pip python3-zmq libcurl4-gnutls-dev libgnutls28-dev cntlm libyaml-dev
mkdir /etc/manager
cd /opt/manager
python3 setup.py install

# Register sensor manager service
ln -s /etc/systemd/system/manager.service /etc/systemd/system/multi-user.target.wants/manager.service

# Disable IPv6
echo "net.ipv6.conf.all.disable_ipv6 = 1" > /etc/sysctl.d/70-disable-ipv6.conf

# Restrict SSH access to Ethernet-over-USB connections
sed -i 's/#ListenAddress 0.0.0.0/ListenAddress 192.168.7.2/g' /etc/ssh/sshd_config
sed -i 's/#Port 22/Port 22222/g' /etc/ssh/sshd_config

# Disable systemd's NTP, because it interferes with HTTPS time sync
rm /etc/systemd/system/sysinit.target.wants/systemd-timesyncd.service

# Disable the dnsmasq daemon that usually launches as part of the USB ethernet gadget, but listens on 0.0.0.0 and blocks port 53
mv /etc/dnsmasq.d /etc/dnsmasq.d.disabled

# Disable ConnMan, a network manager that conflicts with our sensor manager
systemctl disable connman

# Fix /etc/resolv.conf symlink (occupied by connman)
rm -v /etc/resolv.conf
dpkg-reconfigure resolvconf

# Disable background APT activity
apt --purge remove -y unattended-upgrades

# Clean up APT cache
apt clean

# Revision marker
echo $1 > /revision
