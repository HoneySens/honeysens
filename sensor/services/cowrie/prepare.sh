#!/usr/bin/env sh
set -e

# Install requirements
apk --update --no-cache add --virtual build-dependencies gcc g++ git curl py-virtualenv mpfr-dev mpc1-dev gmp-dev musl-dev python2-dev
apk --update add bash openssh-keygen openssh-client openssl-dev libffi-dev py-pip zeromq-dev

# Install cowrie from git
curl -s -L https://github.com/cowrie/cowrie/archive/1.6.0.tar.gz -o /root/cowrie.tar.gz
tar -xzf /root/cowrie.tar.gz -C /opt
mv -v /root/honeysens.py /opt/cowrie-1.6.0/src/cowrie/output/
mv -v /root/cowrie.cfg /opt/cowrie-1.6.0/etc/
mv -v /root/run.sh /opt/
(cd /opt/cowrie-1.6.0 && patch -p1 -i /root/dont_daemonize.patch)
pip install --no-cache-dir -r /opt/cowrie-1.6.0/requirements.txt bcrypt pyzmq

# Setup permissions
adduser -D -s /bin/sh cowrie cowrie
chmod -R 777 /opt/cowrie-1.6.0/var

# Clean up
apk del build-dependencies
rm -rf /var/cache/apk/* /root/*
