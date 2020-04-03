#!/usr/bin/env bash
set -e

echo "Performing a deployment reset, ALL DATA WILL GET LOST IN THE PROCESS"
echo "  Verifying service status"

if nc -z server 443 2>/dev/null; then
    echo "  Can't perform reset: API service is reachable, please shut it down first"
    exit 1
else
    echo "  API: down (OK)"
fi

if nc -z "${REGISTRY_HOST}" "${REGISTRY_PORT}" 2>/dev/null; then
    echo "  Can't perform reset: Registry service is reachable, please shut it down first"
    exit 1
else
    echo "  Registry: down (OK)"
fi

if mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" -e '\q'; then
    echo "  Database: up (OK)"
else
    echo "  Can't perform reset: Database is not accessible"
    exit 1
fi

echo "  Cleaning up volumes"
rm -rf /srv/data/* /srv/registry/*

echo "  Cleaning up database"
echo "DROP DATABASE ${DB_NAME}; CREATE DATABASE ${DB_NAME}" | mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}"

echo "Reset completed. Please restart the other service to resume operation."
