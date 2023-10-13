#!/usr/bin/env sh
set -e

# Install requirements
apk --update --no-cache add --virtual build-dependencies build-base git libffi-dev libxslt-dev php7-dev openssl-dev python2-dev
apk --update add libstdc++ libxslt make php7 py-mysqldb py-openssl py2-pip

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
echo "zend_extension = "$(find /usr -name bfr.so) >> /etc/php7/php.ini

# Install glastopf from git
git clone https://github.com/mushorg/glastopf.git /opt/glastopf
cd /opt/glastopf
git checkout d17fcb6d8d5fb082af7ea3ef1abbe173895568d9
mv /root/requirements.txt /opt/glastopf/
mv /root/log_honeysens.py /opt/glastopf/glastopf/modules/reporting/auxiliary/
pip2 install --no-cache-dir .
mv /root/run.sh /opt/

# Setup user, groups and configs
addgroup -g 2000 glastopf
adduser -S -H -u 2000 -D -g 2000 glastopf
mkdir -p /etc/glastopf
mv /root/glastopf.cfg /etc/glastopf/

# Clean up
apk del build-dependencies
rm -rf /root/* /opt/BFR /opt/glastopf /var/cache/apk/* /tmp/* /var/tmp/*
