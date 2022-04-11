#!/usr/bin/env bash
set -e

# Apache
chown -R hs:hs /opt/HoneySens/cache/ /opt/HoneySens/data/
mkdir -p /etc/apache2/conf
cp -v /opt/HoneySens/utils/docker/apache.http.conf /etc/apache2/sites-available/honeysens_http.conf
cp -v /opt/HoneySens/utils/docker/apache.ssl.conf /etc/apache2/sites-available/honeysens_ssl.conf
cp -v /opt/HoneySens/utils/docker/apache.ssl_proxy_auth.conf /etc/apache2/sites-available/honeysens_ssl_proxy_auth.conf
cp -v /opt/HoneySens/utils/docker/apache.public.conf /etc/apache2/conf/honeysens.public.conf

# Cron and sudo configuration
cp -v /opt/HoneySens/utils/docker/cron.conf /etc/cron.d/honeysens
cp -v /opt/HoneySens/utils/docker/sudoers.conf /etc/sudoers.d/honeysens

# Init scripts
cp -v /opt/HoneySens/utils/docker/my_init.d/01_init_volumes.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/02_regen_honeysens_ca.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/03_regen_https_cert.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/05_init_apache.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/06_update_deployment.py /etc/my_init.d/

# Create templates from the data directory to allow reinitialization of empty volumes
mkdir -p /opt/HoneySens/templates
cp -var /opt/HoneySens/data /opt/HoneySens/templates/
chown -R hs:hs /opt/HoneySens/templates

# Services
cp -vr /opt/HoneySens/utils/docker/services/apache2 /etc/service

# TLS key and certs
ln -s /opt/HoneySens/data/https.chain.crt /srv/tls/https.crt
ln -s /opt/HoneySens/data/https.key /srv/tls/https.key
