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

if nc -z "${HS_REGISTRY_HOST}" "${HS_REGISTRY_PORT}" 2>/dev/null; then
    echo "  Can't perform reset: Registry service is reachable, please shut it down first"
    exit 1
else
    echo "  Registry: down (OK)"
fi

if mysql -h "${HS_DB_HOST}" -P "${HS_DB_PORT}" -u "${HS_DB_USER}" -p"${HS_DB_PASSWORD}" "${HS_DB_NAME}" -e '\q'; then
    echo "  Database: up (OK)"
else
    echo "  Can't perform reset: Database is not accessible"
    exit 1
fi

echo "  Cleaning up volumes"
rm -rf /srv/data/* /srv/registry/*

echo "  Cleaning up database"
echo "DROP DATABASE ${HS_DB_NAME}; CREATE DATABASE ${HS_DB_NAME}" | mysql -h "${HS_DB_HOST}" -P "${HS_DB_PORT}" -u "${HS_DB_USER}" -p"${HS_DB_PASSWORD}" "${HS_DB_NAME}"

echo "Reset completed. Please restart the other service to resume operation."
