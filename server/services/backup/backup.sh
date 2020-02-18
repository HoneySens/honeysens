#!/usr/bin/env bash
set -e

mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" >/srv/db.sql
if [[ "${1}" == "-l" ]]; then
  TARGET=$(printf "/srv/backup/${CRON_TEMPLATE}.tar.bz2" "$(date +%Y%m%e-%H%M)")
  echo "Writing backup to ${TARGET}"
else
  TARGET="-"
fi
tar -cjf ${TARGET} -C /srv registry data db.sql
rm /srv/db.sql