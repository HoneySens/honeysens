#!/usr/bin/env bash
set -e

# 'data' wasn't writeable prior to version 0.2.0. This script attempts to fix some basic permission issues.
chmod -R 777 /opt/HoneySens/data /opt/HoneySens/cache
chown -R www-data:www-data /opt/HoneySens/data
