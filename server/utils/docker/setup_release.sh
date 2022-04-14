#!/usr/bin/env bash
set -e

# Apache
chown -R hs:hs /opt/HoneySens/cache/ /opt/HoneySens/data/
cp -v /opt/HoneySens/utils/docker/apache.ports.conf /etc/apache2/ports.conf
cp -v /opt/HoneySens/utils/docker/apache.http.conf /etc/apache2/sites-available/honeysens_http.conf
cp -v /opt/HoneySens/utils/docker/apache.ssl.conf /etc/apache2/sites-available/honeysens_ssl.conf
cp -v /opt/HoneySens/utils/docker/apache.public.conf /etc/apache2/conf/honeysens.public.conf

# Init scripts
cp -v /opt/HoneySens/utils/docker/startup.d/01_init_volumes.sh /etc/startup.d/
cp -v /opt/HoneySens/utils/docker/startup.d/02_regen_honeysens_ca.sh /etc/startup.d/
cp -v /opt/HoneySens/utils/docker/startup.d/03_regen_https_cert.sh /etc/startup.d/
cp -v /opt/HoneySens/utils/docker/startup.d/04_init_apache.sh /etc/startup.d/
cp -v /opt/HoneySens/utils/docker/startup.d/05_update_deployment.py /etc/startup.d/
cp -v /opt/HoneySens/utils/docker/startup.d/06_launch_httpd.sh /etc/startup.d/
cp -v /opt/HoneySens/utils/docker/run.sh /opt/

# Create templates from the data directory to allow reinitialization of empty volumes
mkdir -p /opt/HoneySens/templates
cp -var /opt/HoneySens/data /opt/HoneySens/templates/
chown -R hs:hs /opt/HoneySens/templates

# TLS key and certs
ln -s /opt/HoneySens/data/https.chain.crt /srv/tls/https.crt
ln -s /opt/HoneySens/data/https.key /srv/tls/https.key
