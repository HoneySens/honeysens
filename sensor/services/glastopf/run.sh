#!/usr/bin/env sh

# Generate random CN
CN=`pwgen 12 1`

mkdir -p /tmp/glastopf
cp /opt/glastopf/glastopf.cfg /tmp/glastopf/
openssl req -new -x509 -days 3650 -nodes -config /opt/openssl.conf -out /tmp/glastopf/stunnel.pem -keyout /tmp/glastopf/stunnel.pem -subj "/CN=$CN"
cd /tmp/glastopf
/usr/bin/stunnel /opt/stunnel.conf &
glastopf-runner
