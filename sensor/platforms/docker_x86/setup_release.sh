#!/usr/bin/env bash
set -e

# Build sensor manager
mkdir /etc/manager
cd /opt/manager
python setup.py install

# Clean up
apk del build-dependencies
rm -f /var/cache/apk/*
rm -rf .cache/pip
