#!/usr/bin/env bash
set -e

# Build sensor manager
mkdir /etc/manager
pip3 install /opt/manager

# Clean up
apk del build-dependencies
rm -f /var/cache/apk/*
rm -rf .cache/pip
