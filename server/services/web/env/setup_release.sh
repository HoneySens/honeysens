#!/usr/bin/env bash
set -e

# Apache
mkdir -vp /srv/api/cache /opt/HoneySens/data
chown -R ubuntu:ubuntu /srv/api/cache /opt/HoneySens/data
cp -v /srv/env/apache/apache.frontend.local.conf /etc/apache2/conf/frontend.conf
cp -v /srv/env/apache/apache.web.conf /etc/apache2/conf/web.conf
cp -v /srv/env/apache/apache.http.api.conf /etc/apache2/sites-available/honeysens_http_api.conf
cp -v /srv/env/apache/apache.http.redirect.conf /etc/apache2/sites-available/honeysens_http_redirect.conf
cp -v /srv/env/apache/apache.https.conf /etc/apache2/sites-available/honeysens_https.conf

# Init scripts
cp -v /srv/env/startup.d/01_init_volumes.sh /etc/startup.d/
cp -v /srv/env/startup.d/02_regen_honeysens_ca.sh /etc/startup.d/
cp -v /srv/env/startup.d/03_regen_https_cert.sh /etc/startup.d/
cp -v /srv/env/startup.d/04_init_apache.sh /etc/startup.d/
cp -v /srv/env/startup.d/05_update_deployment.py /etc/startup.d/
cp -v /srv/env/startup.d/06_launch_httpd.sh /etc/startup.d/

# TLS key and certs
ln -s /opt/HoneySens/data/https.chain.crt /srv/tls/https.crt
ln -s /opt/HoneySens/data/https.key /srv/tls/https.key
