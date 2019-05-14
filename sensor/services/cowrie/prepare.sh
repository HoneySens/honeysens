#!/usr/bin/env sh

apk --update add git gcc g++ bash curl openssh-keygen openssh-client zeromq-dev py-pip py-virtualenv musl-dev python2-dev mpfr-dev openssl-dev mpc1-dev libffi-dev gmp-dev
adduser -D -s /bin/sh cowrie cowrie
curl -s -L https://github.com/cowrie/cowrie/archive/1.6.0.tar.gz -o /root/cowrie.tar.gz
tar -xzf /root/cowrie.tar.gz -C /opt
mv -v /root/honeysens.py /opt/cowrie-1.6.0/src/cowrie/output/
mv -v /root/cowrie.cfg /opt/cowrie-1.6.0/etc/
(cd /opt/cowrie-1.6.0 && patch -p1 -i /root/dont_daemonize.patch)
pip install --no-cache-dir -r /opt/cowrie-1.6.0/requirements.txt bcrypt pyzmq
chmod -R 777 /opt/cowrie-1.6.0/var
rm -rf /var/cache/apk/* /root/*
