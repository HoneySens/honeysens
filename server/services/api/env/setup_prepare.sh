#!/usr/bin/env bash
# Basic container initialization for both productive and development environments
set -e

export DEBIAN_FRONTEND=noninteractive
apt-get -qq update
apt-get upgrade -y

# Basic dependencies
apt-get install -y software-properties-common screen python3-openssl python3-pymysql curl openssl apache2 vim iproute2 netcat-openbsd

# PHP 8
apt-get install -y php8.3 php8.3-curl php8.3-mbstring php8.3-mysql php8.3-xml php8.3-ldap
# Re-enable PHPs built-in session garbage collector
sed -i -e 's/session.gc_probability.*/session.gc_probability = 1/' -e 's/session.gc_divisor.*/session.gc_divisor = 1000/' /etc/php/8.3/apache2/php.ini
chmod go+r /var/lib/php/sessions

# Apache
sed -i -e 's/upload_max_filesize.*/upload_max_filesize = 100M/' -e 's/post_max_size.*/post_max_size = 100M/' /etc/php/8.3/apache2/php.ini
sed -i -e 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-enabled/security.conf
a2enmod rewrite ssl headers proxy_http
a2dissite 000-default
mkdir -vp /etc/apache2/conf
chmod 755 /var/run/screen # see https://github.com/stucki/docker-cyanogenmod/issues/2

# Init and TLS Directories
mkdir -vp /etc/startup.d /srv/data /srv/tls

# Permissions
chown -R ubuntu:ubuntu /etc/apache2 /var/lib/apache2 /var/log/apache2 /run /srv
