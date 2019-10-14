#!/usr/bin/env sh

# Remove potential stale PID file, otherwise twistd might not start
rm -f /opt/cowrie-1.6.0/var/run/cowrie.pid

exec /opt/cowrie-1.6.0/bin/cowrie start