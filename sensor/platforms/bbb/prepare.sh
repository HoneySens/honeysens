#!/usr/bin/env sh

# Skip interactive apt dialogues
export DEBIAN_FRONTEND=noninteractive

# Update packet repository
apt-get update

# Basic dependencies
apt-get -y install macchanger resolvconf tcpdump wpasupplicant

# Install Docker CE (follows https://docs.docker.com/engine/installation/linux/docker-ce/debian)
apt-get -y install apt-transport-https curl gnupg-agent software-properties-common
curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add -
add-apt-repository "deb [arch=armhf] https://download.docker.com/linux/debian $(lsb_release -cs) stable"
apt-get update
apt-get install -y docker-ce

# Build sensor manager
apt-get install -y python-pip libcurl4-gnutls-dev libgnutls28-dev cntlm libyaml-dev libzmq3-dev
mkdir /etc/manager
cd /opt/manager
python setup.py install

# Register sensor manager service
ln -s /etc/systemd/system/manager.service /etc/systemd/system/multi-user.target.wants/manager.service

# Restrict SSH access to Ethernet-over-USB connections
sed -i 's/#ListenAddress 0.0.0.0/ListenAddress 192.168.7.2/g' /etc/ssh/sshd_config
sed -i 's/#Port 22/Port 22222/g' /etc/ssh/sshd_config

# Disable systemd's NTP, because it interferes with HTTPS time sync
rm /etc/systemd/system/sysinit.target.wants/systemd-timesyncd.service

# Disable the dnsmasq daemon that usually launches as part of the USB ethernet gadget, but listens on 0.0.0.0 and blocks port 53
mv /etc/dnsmasq.d /etc/dnsmasq.d.disabled

# Disable ConnMan, a network manager that conflicts with our sensor manager
systemctl disable connman

# Disable background APT activity
apt-get --purge remove -y unattended-upgrades

# Clean up APT cache
apt-get clean

# Revision marker
echo $1 > /revision
