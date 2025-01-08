#!/usr/bin/env bash
set -e

# Apache
chown -R ubuntu:ubuntu /opt/HoneySens/cache/ /opt/HoneySens/data/
cp -v /opt/HoneySens/utils/docker/apache.http.api.conf /etc/apache2/sites-available/honeysens_http_api.conf
cp -v /opt/HoneySens/utils/docker/apache.http.redirect.conf /etc/apache2/sites-available/honeysens_http_redirect.conf
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
chown -R ubuntu:ubuntu /opt/HoneySens/templates

# TLS key and certs
ln -s /opt/HoneySens/data/https.chain.crt /srv/tls/https.crt
ln -s /opt/HoneySens/data/https.key /srv/tls/https.key
