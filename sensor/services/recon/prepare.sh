#!/usr/bin/env sh
set -x

apk --update --no-cache add --virtual build-dependencies build-base python2-dev py-pip zeromq-dev
apk --update --no-cache add iptables py2-netifaces scapy zeromq

pip install pyzmq

apk del build-dependencies
rm -f /var/cache/apk/*
rm -rf .cache/pip