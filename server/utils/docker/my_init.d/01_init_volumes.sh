#!/bin/bash
set -e
# Initialize /opt/HoneySens/data with a new template from /opt/HoneySens/templates/data in case it looks uninitialized

if [[ ! -f /opt/HoneySens/data/config.cfg ]]; then
    echo "NOTICE: Initializing data volume with new template"
    cp -var /opt/HoneySens/templates/data/. /opt/HoneySens/data/
fi
