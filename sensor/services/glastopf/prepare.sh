#cython !/usr/bin/env sh
# Based on https://hub.docker.com/r/dtagdevsec/glastopf/~/dockerfile/
set -e

# Install requirements
apk --no-cache add autoconf bind-tools build-base git libffi libffi-dev libpcap libxslt-dev make php7 php7-dev openssl-dev py-mysqldb py-openssl py-pip py-setuptools python python-dev

# ZMQ
pip install pyzmq

# Install php sandbox from git
git clone https://github.com/mushorg/BFR /opt/BFR
cd /opt/BFR
git checkout 508729202428a35bcc6bb27dd97b831f7e5009b5
phpize7
./configure --with-php-config=/usr/bin/php-config7 --enable-bfr
make
make install
cd /
rm -rf /opt/BFR /tmp/* /var/tmp/*
echo "zend_extension = "$(find /usr -name bfr.so) >> /etc/php7/php.ini

# Install glastopf from git
git clone https://github.com/mushorg/glastopf.git /opt/glastopf
cd /opt/glastopf
git checkout f9ac53e685991ffe2402f9cb3eb5ccdc2d0b198c
mv /root/requirements.txt /opt/glastopf/
mv /root/log_honeysens.py /opt/glastopf/glastopf/modules/reporting/auxiliary/
pip install --no-cache-dir .
cd /
rm -rf /opt/glastopf /tmp/* /var/tmp/*
mv /root/run.sh /opt/

# Setup user, groups and configs
addgroup -g 2000 glastopf
adduser -S -H -u 2000 -D -g 2000 glastopf
mkdir -p /etc/glastopf
mv /root/glastopf.cfg /etc/glastopf/

# Clean up
apk del autoconf build-base file git libffi-dev php7-dev python-dev py-pip
rm -rf /root/*
rm -rf /var/cache/apk/*
