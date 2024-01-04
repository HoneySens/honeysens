#!/usr/bin/env bash
# Basic container initialization for both productive and development environments
set -e

export DEBIAN_FRONTEND=noninteractive
apt-get -qq update
apt-get upgrade -y

# Unprivileged user creation
groupadd -g 1000 hs
useradd -m -u 1000 -g 1000 hs

# Basic dependencies
apt-get install -y software-properties-common screen python3-openssl python3-pymysql curl openssl apache2 vim iproute2 netcat php7.4 php7.4-mbstring php7.4-mysql php7.4-xml php7.4-ldap

# Apache
sed -i -e 's/upload_max_filesize.*/upload_max_filesize = 100M/' -e 's/post_max_size.*/post_max_size = 100M/' /etc/php/7.4/apache2/php.ini
sed -i -e 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-enabled/security.conf
a2enmod rewrite ssl headers proxy_http
a2dissite 000-default
mkdir -p /etc/apache2/conf
chmod 755 /var/run/screen # see https://github.com/stucki/docker-cyanogenmod/issues/2

# Init and TLS Directories
mkdir -p /etc/startup.d /srv/data /srv/tls

# Permissions
chown -R hs:hs /etc/apache2 /var/lib/apache2 /var/log/apache2 /run /srv
