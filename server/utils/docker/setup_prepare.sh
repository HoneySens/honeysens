#!/usr/bin/env bash
# Basic container initialization for both productive and development environments

export DEBIAN_FRONTEND=noninteractive
apt-get -qq update

# Basic dependencies
apt-get install -y mysql-server beanstalkd screen python python-yaml python-openssl curl openssl apache2 sudo

# PHP 5
add-apt-repository -y ppa:ondrej/php
apt-get -qq update
apt-get install -y php5.6 php5.6-mbstring php5.6-mysql php5.6-xml libapache2-mod-php5.6

# Beanstalk
sed -i -e 's/#START=yes/START=yes/' -e 's/BEANSTALKD_LISTEN_ADDR=.*/BEANSTALKD_LISTEN_ADDR=127.0.0.1/' /etc/default/beanstalkd

# MySQL
ln -fs /dev/stdout /var/log/mysql/error.log
/etc/init.d/mysql start
mysql -u root -e "CREATE DATABASE honeysens"
mysql -u root honeysens -e "GRANT ALL PRIVILEGES ON honeysens.* TO honeysens@localhost IDENTIFIED BY 'honeysens'"
/etc/init.d/mysql stop

# Apache
sed -i -e 's/upload_max_filesize.*/upload_max_filesize = 100M/' -e 's/post_max_size.*/post_max_size = 100M/' /etc/php/5.6/apache2/php.ini
echo www-data > /etc/container_environment/APACHE_RUN_USER
echo www-data > /etc/container_environment/APACHE_RUN_GROUP
echo /var/log/apache2 > /etc/container_environment/APACHE_LOG_DIR
echo /var/lock/apache2 > /etc/container_environment/APACHE_LOCK_DIR
echo /var/run/apache2.pid > /etc/container_environment/APACHE_PID_FILE
echo /var/run/apache2 > /etc/container_environment/APACHE_RUN_DIR
a2enmod rewrite ssl headers proxy_http
a2dissite 000-default
chmod 755 /var/run/screen # see https://github.com/stucki/docker-cyanogenmod/issues/2

# Install skopeo
add-apt-repository -y ppa:longsleep/golang-backports # see https://github.com/golang/go/wiki/Ubuntu
add-apt-repository -y ppa:alexlarsson/flatpak # required for libostree, which is missing in Ubuntu Xenial
apt-get -qq update
apt-get install -y --allow-unauthenticated git-core golang-go btrfs-tools libdevmapper-dev libgpgme11-dev go-md2man libglib2.0-dev libostree-dev
curl -Ls https://github.com/projectatomic/skopeo/archive/v0.1.28.tar.gz --output /opt/skopeo.tar.gz
tar -xzf /opt/skopeo.tar.gz -C /opt/
mkdir -p /opt/go/src/github.com/projectatomic
mv /opt/skopeo-0.1.28 /opt/go/src/github.com/projectatomic/skopeo
export GOPATH=/opt/go
make -C /opt/go/src/github.com/projectatomic/skopeo binary-local
make -C /opt/go/src/github.com/projectatomic/skopeo install
