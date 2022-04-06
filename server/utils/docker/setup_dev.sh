#!/usr/bin/env bash
set -e
export DEBIAN_FRONTEND=noninteractive

# Additional dev requirements
apt-get install -y npm nodejs wget unzip texlive-base texlive-latex-extra texlive-extra-utils figlet boxes git
npm install -g grunt-cli

# Create dummy template config to prevent data initialization in 01_init_volumes.sh
mkdir -p /opt/HoneySens/data
touch /opt/HoneySens/data/config.cfg
chown -R hs:hs /opt/HoneySens/data

# Disable LDAP certificate verification within the dev environment
echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf

# Permit write access to startup scripts
chown hs:hs /etc/startup.d
