#!/usr/bin/env bash
set -e

mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" -y "${DB_NAME}" >/srv/db.sql
if [[ "${1}" == "-l" ]]; then
  TARGET=$(printf "/srv/backup/${CRON_TEMPLATE}.tar.bz2" "$(date +%Y%m%d-%H%M)")
  # Remove old backups
  if [[ -n "${CRON_KEEP}" && "${CRON_KEEP}" -gt 0 && $(find /srv/backup -name '*.tar.bz2' | wc -l) -gt 0 ]]; then
    echo "Cleaning up old backups"
    OIFS="$IFS"
    IFS=$'\n'
    for f in $(ls -t /srv/backup/*.tar.bz2 | tail -n +"${CRON_KEEP}"); do
      rm -v "${f}"
    done
    IFS="$OIFS"
  fi
  echo "Writing backup to ${TARGET}"
else
  TARGET="-"
fi
# Full backup or DB backup depending on params
if [[ "${1}" == "-d" || ("${1}" == "-l" && "${CRON_DBONLY}" == "true") ]]; then
  tar -cjf ${TARGET} -C /srv db.sql
else
  tar -cjf ${TARGET} -C /srv registry data db.sql
fi
rm /srv/db.sql