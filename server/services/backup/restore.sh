#!/usr/bin/env bash
set -e

echo "Performing restoration from backup archive"
echo "  Verifying service status"

if nc -z server 443 2>/dev/null; then
    echo "  Can't perform restoration: API service is reachable, please shut it down first"
    exit 1
else
    echo "  API: down (OK)"
fi

if nc -z "${HS_REGISTRY_HOST}" "${HS_REGISTRY_PORT}" 2>/dev/null; then
    echo "  Can't perform restoration: Registry service is reachable, please shut it down first"
    exit 1
else
    echo "  Registry: down (OK)"
fi

if mysql -h "${HS_DB_HOST}" -P "${HS_DB_PORT}" -u "${HS_DB_USER}" -p"${HS_DB_PASSWORD}" "${HS_DB_NAME}" -e '\q'; then
    echo "  Database: up (OK)"
else
    echo "  Can't perform restoration: Database is not accessible"
    exit 1
fi

echo "  Extracting archive"
mkdir -p /tmp/restore
tar -xjpf - -C /tmp/restore

echo "  Verifying archive contents"
if [[ ! -f /tmp/restore/db.sql ]]; then
    echo "  Can't perform restoration: The backup archive is incomplete"
    exit 1
fi

if [[ -d /tmp/restore/data && -d /tmp/restore/registry ]]; then
  echo "  Importing volumes"
  rm -rf /srv/data/* /srv/registry/*
  mv -f /tmp/restore/data/* /srv/data/ >/dev/null 2>&1 || true
  mv -f /tmp/restore/registry/* /srv/registry/ >/dev/null 2>&1 || true
fi

echo "  Importing database"
echo "DROP DATABASE ${HS_DB_NAME}; CREATE DATABASE ${HS_DB_NAME}" | mysql -h "${HS_DB_HOST}" -P "${HS_DB_PORT}" -u "${HS_DB_USER}" -p"${HS_DB_PASSWORD}" "${HS_DB_NAME}"
mysql -h "${HS_DB_HOST}" -P "${HS_DB_PORT}" -u "${HS_DB_USER}" -p"${HS_DB_PASSWORD}" "${HS_DB_NAME}" </tmp/restore/db.sql

echo "  Cleaning up"
rm -rf /tmp/restore

echo "Restoration completed. Please restart the other services to resume operation."
