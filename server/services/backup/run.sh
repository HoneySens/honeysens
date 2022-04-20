#!/usr/bin/env bash
set -e

if [[ "${CRON_ENABLED}" == "true" ]]; then
    echo "Backup service ready (scheduler running)"
    echo -e "${CRON_CONDITION} backup -l" > /srv/crontab
    exec supercronic /srv/crontab
else
    echo "Backup service ready"
    exec sleep infinity
fi
