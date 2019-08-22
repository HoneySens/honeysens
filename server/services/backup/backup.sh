#!/usr/bin/env bash

mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" >/srv/db.sql
tar -cjf - -C /srv registry data db.sql
rm /srv/db.sql