#!/usr/bin/env bash
# Basic container initialization for both productive and development environments
set -e

export DEBIAN_FRONTEND=noninteractive
apt-get -qq update

# Basic dependencies
apt-get install -y screen python python-yaml python-openssl python-pymysql python-pip python-celery-common python3-redis curl openssl apache2 sudo

# PHP 5
add-apt-repository -y ppa:ondrej/php
apt-get -qq update
apt-get install -y php5.6 php5.6-mbstring php5.6-mysql php5.6-xml php5.6-ldap libapache2-mod-php5.6

# Apache
sed -i -e 's/upload_max_filesize.*/upload_max_filesize = 100M/' -e 's/post_max_size.*/post_max_size = 100M/' /etc/php/5.6/apache2/php.ini
sed -i -e 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-enabled/security.conf
echo www-data > /etc/container_environment/APACHE_RUN_USER
echo www-data > /etc/container_environment/APACHE_RUN_GROUP
echo /var/log/apache2 > /etc/container_environment/APACHE_LOG_DIR
echo /var/lock/apache2 > /etc/container_environment/APACHE_LOCK_DIR
echo /var/run/apache2.pid > /etc/container_environment/APACHE_PID_FILE
echo /var/run/apache2 > /etc/container_environment/APACHE_RUN_DIR
a2enmod rewrite ssl headers proxy_http
a2dissite 000-default
chmod 755 /var/run/screen # see https://github.com/stucki/docker-cyanogenmod/issues/2

# TLS Directory
mkdir -p /srv/tls
