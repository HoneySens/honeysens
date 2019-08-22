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

if nc -z honeysens-registry 5000 2>/dev/null; then
    echo "  Can't perform restoration: Registry service is reachable, please shut it down first"
    exit 1
else
    echo "  Registry: down (OK)"
fi

if mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" -e '\q'; then
    echo "  Database: up (OK)"
else
    echo "  Can't perform restoration: Database is not accessible"
    exit 1
fi

echo "  Extracting archive"
mkdir -p /tmp/restore
tar -xjpf - -C /tmp/restore

echo "  Verifying archive contents"
if [[ ! -d /tmp/restore/data || ! -d /tmp/restore/registry || ! -f /tmp/restore/db.sql ]]; then
    echo "  Can't perform restoration: The backup archive is incomplete"
    exit 1
fi

echo "  Importing volumes"
rm -rf /srv/data/* /srv/registry/*
mv -f /tmp/restore/data/* /srv/data/ >/dev/null 2>&1 || true
mv -f /tmp/restore/registry/* /srv/registry/ >/dev/null 2>&1 || true

echo "  Importing database"
echo "DROP DATABASE ${DB_NAME}; CREATE DATABASE ${DB_NAME}" | mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}"
mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" </tmp/restore/db.sql

echo "  Cleaning up"
rm -rf /tmp/restore

echo "Restoration completed. Please restart the other services to resume operation."
