#!/usr/bin/env sh

apk --update add git gcc g++ bash curl openssh-keygen openssh-client zeromq py-pip py-virtualenv musl-dev python2-dev mpfr-dev openssl-dev mpc1-dev libffi-dev gmp-dev
rm -f /var/cache/apk/*
adduser -D -s /bin/sh cowrie cowrie
su - cowrie -c "curl -s -L https://github.com/micheloosterhof/cowrie/archive/v1.2.0.tar.gz -o /home/cowrie/cowrie.tar.gz"
su - cowrie -c "tar -xzf /home/cowrie/cowrie.tar.gz -C /home/cowrie"
cd /home/cowrie/cowrie-1.2.0
mv /home/cowrie/honeysens.py ./cowrie/output/
mv /home/cowrie/cowrie.cfg .
patch -p1 </home/cowrie/dont_daemonize.patch
patch -p1 </home/cowrie/config.patch
su - cowrie -c "cd /home/cowrie/cowrie-1.2.0 && virtualenv cowrie-env"
pip install Twisted==17.9.0
pip install -r requirements.txt
pip install pyzmq
