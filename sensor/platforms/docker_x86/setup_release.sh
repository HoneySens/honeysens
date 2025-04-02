#!/usr/bin/env bash
set -e

# Build sensor manager
mkdir /etc/manager
python3 -m venv --system-site-packages /srv/manager/venv
/srv/manager/venv/bin/pip3 install /srv/manager

# Clean up
apk del build-dependencies
rm -f /var/cache/apk/*
rm -rf .cache/pip
