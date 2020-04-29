#!/usr/bin/env sh
set -e

apk --update --no-cache add --virtual build-dependencies build-base mariadb-dev
apk --update --no-cache add curl zeromq py-pip py-virtualenv python2-dev libxml2-dev libxslt-dev mariadb-client git libffi-dev tcpdump zeromq-dev mariadb-connector-c
adduser -D -s /bin/sh conpot conpot
curl -s -L https://github.com/mushorg/conpot/archive/Release_0.5.2.tar.gz -o /root/conpot.tar.gz
tar -xzf /root/conpot.tar.gz -C /tmp
mv -v /root/honeysens_log.py /tmp/conpot-Release_0.5.2/conpot/core/loggers/
cp -vr /root/http /tmp/conpot-Release_0.5.2/conpot/templates/default/
(cd /tmp/conpot-Release_0.5.2 && patch -p1 -i /root/log_worker.patch)
(cd /tmp/conpot-Release_0.5.2 && patch -p1 -i /root/config.patch)
# Workaround for compilation issues with recent versions of mariadb
# See https://github.com/DefectDojo/django-DefectDojo/issues/407
sed '/st_mysql_options options;/a unsigned int reconnect;' /usr/include/mysql/mysql.h -i.bkp
pip install --no-cache-dir -r /tmp/conpot-Release_0.5.2/requirements.txt pyzmq
(cd /tmp/conpot-Release_0.5.2 && python setup.py install)
cp -v /tmp/conpot-Release_0.5.2/conpot/testing.cfg /srv/conpot.cfg
apk del build-dependencies build-base mariadb-dev # Build-time dependency removal
rm -rf /tmp/* /var/tmp/* /var/cache/apk/* /root/*
