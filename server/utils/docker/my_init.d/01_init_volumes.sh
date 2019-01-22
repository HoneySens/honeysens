#!/bin/bash
set -e
# Initialize /opt/HoneySens/data and /var/lib/mysql with a new directory template from
# /opt/HoneySens/templates/data in case it looks uninitialized

if [[ ! -f /opt/HoneySens/data/config.cfg ]]; then
    echo "NOTICE: Initializing data volume with new template"
    cp -var /opt/HoneySens/templates/data/. /opt/HoneySens/data/
fi

if [[ ! -f /var/lib/mysql/ibdata1 ]]; then
    echo "NOTICE: Initializing database volume with new template"
    cp -var /opt/HoneySens/templates/mysql/. /var/lib/mysql/
fi