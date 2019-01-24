#!/usr/bin/env sh
# Based on https://hub.docker.com/r/dtagdevsec/glastopf/~/dockerfile/

# Install requirements
apk -U --no-cache add autoconf bash bind-tools build-base cython git libffi libffi-dev make py-asn1 py-cffi py-chardet py-cparser py-cryptography py-dateutil py-enum34 py-idna py-ipaddress py-jinja2 py-lxml py-mysqldb py-openssl py-pip py-requests py-setuptools python python-dev python2-dev gcc g++ zeromq stunnel openssl pwgen
apk -U add --repository http://dl-3.alpinelinux.org/alpine/edge/testing/ py-beautifulsoup4 php7 php7-dev py-cssselect py-gevent py-greenlet py-mongo py-sqlalchemy py-webob

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
git checkout c4932d9cb513d284142e2c0d66284221201d7477
mv /root/log_honeysens.py /opt/glastopf/glastopf/modules/reporting/auxiliary/
python setup.py install
cd /
rm -rf /opt/glastopf /tmp/* /var/tmp/*
mv /root/run.sh /root/openssl.conf /root/stunnel.conf /opt/

# Setup user, groups and configs
addgroup -g 2000 glastopf
adduser -S -H -u 2000 -D -g 2000 glastopf
mkdir -p /opt/glastopf
mv /root/glastopf.cfg /opt/glastopf/

# Clean up
apk del autoconf build-base git libffi-dev php7-dev python-dev
rm -rf /root/*
rm -rf /var/cache/apk/*
