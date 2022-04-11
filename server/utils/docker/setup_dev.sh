#!/usr/bin/env bash
set -e
export DEBIAN_FRONTEND=noninteractive

# Additional dev requirements
apt-get install -y npm nodejs wget unzip texlive-base texlive-latex-extra texlive-extra-utils figlet boxes git
npm install -g grunt-cli

# Create dummy template config to prevent the data initialization in 01_init_volumes.sh
mkdir -p /opt/HoneySens/data
touch /opt/HoneySens/data/config.cfg
chown -R hs:hs /opt/HoneySens/data

# Disable certificate verification within the dev environment
echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf

# TLS key and certs
ln -s /srv/data/https.chain.crt /srv/tls/https.crt
ln -s /srv/data/https.key /srv/tls/https.key
