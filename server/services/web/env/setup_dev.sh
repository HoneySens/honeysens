#!/usr/bin/env bash
set -e
export DEBIAN_FRONTEND=noninteractive

# Additional dev requirements
apt-get install -y boxes composer figlet git nodejs npm unzip wget

# Disable LDAP certificate verification within the dev environment
echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf

# Permit write access to startup scripts
chown ubuntu:ubuntu /etc/startup.d

# Create top-level node_modules symlink for mounted source directories
ln -svf /srv/frontend/node_modules /node_modules
