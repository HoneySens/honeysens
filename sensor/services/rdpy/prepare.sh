#!/usr/bin/env sh
set -e

apk --update --no-cache add --virtual build-dependencies build-base git libffi-dev musl-dev openssl-dev python2-dev py-pip zeromq-dev
apk --update --no-cache add py-virtualenv python2 openssl libffi zeromq

adduser -D -s /bin/sh rdpy rdpy

git clone https://github.com/citronneur/rdpy /opt/rdpy
cd /opt/rdpy
git checkout cef16a9f64d836a3221a344ca7d571644280d829

patch -p1 < /root/rdpy-rdphoneypot.patch
mv -v /root/honeysens_log.py /opt/rdpy/rdpy/core
mv -v /root/out.rss /opt

pip install --upgrade pip
pip install --no-cache-dir twisted pyopenssl qt4reactor service_identity rsa pyasn1 utils pyzmq pycrypto
python setup.py install

apk del build-dependencies
rm -f /var/cache/apk/*
rm -rf .cache/pip /opt/rdpy
