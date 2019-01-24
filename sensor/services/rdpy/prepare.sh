#!/usr/bin/env sh

apk --update --no-cache add --virtual build-dependencies build-base
apk --update --no-cache add curl zeromq py-pip gcc py-virtualenv python2-dev musl-dev openssl-dev libffi-dev

adduser -D -s /bin/sh rdpy rdpy

su - rdpy -c "curl -L https://github.com/citronneur/rdpy/archive/v1.3.2.tar.gz -o /home/rdpy/rdpy.tar.gz"
su - rdpy -c "tar -xzf /home/rdpy/rdpy.tar.gz -C /home/rdpy/"
cd /home/rdpy/rdpy-1.3.2

patch -p1 < /home/rdpy/rdpy-rdphoneypot.patch
mv /home/rdpy/honeysens_log.py ./rdpy/core

pip install --upgrade pip
pip install --no-cache-dir twisted pyopenssl qt4reactor service_identity rsa pyasn1
pip install --no-cache-dir utils pyzmq pycrypto
python setup.py install

rm -f /var/cache/apk/*
rm -rf .cache/pip
