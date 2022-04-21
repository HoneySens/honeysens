#!/bin/bash
set -e

# Refuse startup if files that don't belong to uid 1000 are found on the data volume (indicates a version <= 2.3.0)
if find /opt/HoneySens/data ! -uid 1000 | grep . >/dev/null 2>&1; then
  echo -e 'Error: Files not owned by uid 1000 found on data volume!\nIf these volumes belong to a server running revision 2.3.0 or earlier, please update to revision 2.4.0 first.'
  exit 1
fi

# Initialize /opt/HoneySens/data with a new template from /opt/HoneySens/templates/data in case it looks uninitialized
if [[ ! -f /opt/HoneySens/data/config.cfg ]]; then
    echo "NOTICE: Initializing data volume with new template"
    cp -var /opt/HoneySens/templates/data/. /opt/HoneySens/data/
fi
