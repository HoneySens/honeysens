#!/usr/bin/env sh

apk --update add scapy py-pip python2-dev gcc g++ zeromq iptables py2-netifaces
rm -f /var/cache/apk/*
pip install pyzmq