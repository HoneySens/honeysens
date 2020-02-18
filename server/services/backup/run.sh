#!/usr/bin/env bash
set -e

if [[ "${CRON_ENABLED}" == "true" ]]; then
    echo "Backup service ready (scheduler running)"
    echo -e "SHELL=/bin/bash\nPATH=/usr/local/bin:/bin:/usr/bin\nBASH_ENV=/tmp/container.env\n${CRON_CONDITION} root backup -l >/proc/1/fd/1 2>/proc/1/fd/2" > /etc/crontab
    declare -p | grep -Ev 'BASHOPTS|BASH_VERSINFO|EUID|PPID|SHELLOPTS|UID' > /tmp/container.env
    exec cron -f
else
    echo "Backup service ready"
    exec sleep infinity
fi