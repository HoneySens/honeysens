#!/usr/bin/env sh

mkdir -p /tmp/glastopf
cp /etc/glastopf/glastopf.cfg /tmp/glastopf/
cd /tmp/glastopf
glastopf-runner
