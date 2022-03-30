#!/bin/bash
set -e

export APACHE_RUN_USER=hs
export APACHE_RUN_GROUP=hs
export APACHE_LOG_DIR=/var/log/apache2
export APACHE_LOCK_DIR=/var/lock/apache2
export APACHE_PID_FILE=/var/run/apache2.pid
export APACHE_RUN_DIR=/var/run/apache2

if [[ -e /var/run/apache2.pid ]]; then
    rm /var/run/apache2.pid
fi
exec /usr/sbin/apache2 -D FOREGROUND -k start
