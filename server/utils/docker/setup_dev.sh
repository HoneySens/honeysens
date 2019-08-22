#!/usr/bin/env bash
export DEBIAN_FRONTEND=noninteractive

# Additional dev requirements
apt-get install -y npm nodejs-legacy wget unzip texlive-base texlive-latex-extra texlive-extra-utils figlet boxes
npm install -g grunt-cli

# Create dummy template config to prevent the data initialization in 01_init_volumes.sh
mkdir -p /opt/HoneySens/data
touch /opt/HoneySens/data/config.cfg

# Disable certificate verification within the dev environment
echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf
