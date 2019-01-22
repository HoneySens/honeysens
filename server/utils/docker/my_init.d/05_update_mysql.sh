#!/usr/bin/env bash

# Temporarily start
/usr/bin/mysqld_safe &

# Wait for server startup
while ! [[ "$mysqld_process_pid" =~ ^[0-9]+$ ]]; do
  echo "Waiting for mysql to start..."
  mysqld_process_pid=$(echo "$(ps -C mysqld -o pid=)" | sed -e 's/^ *//g' -e 's/ *$//g')
  sleep 2
done

# Update the mysql table metadata if required
/usr/bin/mysql_upgrade

# Shutdown again to apply eventual changes
mysqladmin -u root shutdown

# Wait for server shutdown
while [[ "$mysqld_process_pid" =~ ^[0-9]+$ ]]; do
  echo "Waiting for mysql to shutdown..."
  mysqld_process_pid=$(echo "$(ps -C mysqld -o pid=)" | sed -e 's/^ *//g' -e 's/ *$//g')
  sleep 1
done
