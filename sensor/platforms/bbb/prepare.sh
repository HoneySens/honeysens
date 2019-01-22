#!/usr/bin/env sh

# Skip interactive apt dialogues
export DEBIAN_FRONTEND=noninteractive

# Update packet repository
apt-get update

# Basic dependencies
apt-get -y install macchanger resolvconf

# Install Docker CE (follows https://docs.docker.com/engine/installation/linux/docker-ce/debian)
# TODO Infer the linux-header version from the currently installed linux-image packet (NOT the running kernel)
apt-get -y install apt-transport-https ca-certificates curl gnupg2 software-properties-common linux-headers-4.9.76-ti-r91
curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg | apt-key add -
echo "deb [arch=armhf] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") \
     $(lsb_release -cs) stable" | \
    tee /etc/apt/sources.list.d/docker.list
apt-get update
apt-get install -y docker-ce

# Build sensor manager
apt-get install -y python-pip libcurl4-gnutls-dev libgnutls28-dev cntlm
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

# Clean up apt cache
apt-get clean

# Revision marker
echo $1 > /revision
