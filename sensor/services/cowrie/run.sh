#!/usr/bin/env sh
export COWRIE_STDOUT=yes

# Remove potential stale PID file, otherwise twistd might not start
rm -f /opt/cowrie-2.4.0/var/run/cowrie.pid

exec /opt/cowrie-2.4.0/bin/cowrie start
