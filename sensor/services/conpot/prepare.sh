#!/usr/bin/env sh

apk --update --no-cache add --virtual build-dependencies build-base mariadb-dev # temporary install of MariaDB
apk --update --no-cache add curl zeromq py-pip py-virtualenv python2-dev libxml2-dev libxslt-dev mariadb-client-libs git libffi-dev tcpdump

adduser -D -s /bin/sh conpot conpot

git clone https://github.com/mushorg/conpot /tmp/conpot
(cd /tmp/conpot; git checkout 783422a47a20d93e6a14b1b432e59872930fc03d)
(cd /tmp/conpot; patch -p1 -i /root/config.patch)
(cd /tmp/conpot; patch -p1 -i /root/log_worker.patch)
mv -v /root/honeysens_log.py /tmp/conpot/conpot/core/loggers/
pip install --no-cache-dir -r /tmp/conpot/requirements.txt pyzmq
(cd /tmp/conpot/; python setup.py install)
cp -v /tmp/conpot/conpot/testing.cfg /srv/conpot.cfg

rm -rf /tmp/* /var/tmp/* /var/cache/apk/*
apk del build-dependencies # MariaDB removal
