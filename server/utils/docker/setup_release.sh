#!/usr/bin/env bash

# MySQL
sed -i 's/password.*/password = honeysens/' /opt/HoneySens/data/config.cfg

# Apache
chown -R www-data:www-data /opt/HoneySens/cache/ /opt/HoneySens/data/
cp -v /opt/HoneySens/utils/docker/apache.http.conf /etc/apache2/sites-available/honeysens_http.conf
cp -v /opt/HoneySens/utils/docker/apache.ssl.conf /etc/apache2/sites-available/honeysens_ssl.conf

# Cron and sudo configuration
cp -v /opt/HoneySens/utils/docker/cron.conf /etc/cron.d/honeysens
cp -v /opt/HoneySens/utils/docker/sudoers.conf /etc/sudoers.d/honeysens

# Init scripts
cp -v /opt/HoneySens/utils/docker/my_init.d/01_init_volumes.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/02_regen_honeysens_ca.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/03_regen_https_cert.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/04_fix_permissions.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/05_update_mysql.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.d/06_init_apache.sh /etc/my_init.d/
cp -v /opt/HoneySens/utils/docker/my_init.pre_shutdown.d/01_stop_mysql.sh /etc/my_init.pre_shutdown.d/

# Create templates from both data and database dirs to allow reinitialization of empty volumes
mkdir -p /opt/HoneySens/templates
cp -var /opt/HoneySens/data /var/lib/mysql /opt/HoneySens/templates/

# Services
cp -vr /opt/HoneySens/utils/docker/services/apache2 /etc/service
cp -vr /opt/HoneySens/utils/docker/services/mysql /etc/service
cp -vr /opt/HoneySens/utils/docker/services/beanstalkd /etc/service
cp -vr /opt/HoneySens/utils/docker/services/sensorcfg-creation-worker /etc/service
cp -vr /opt/HoneySens/utils/docker/services/update-worker /etc/service
cp -vr /opt/HoneySens/utils/docker/services/service-registry-worker /etc/service
