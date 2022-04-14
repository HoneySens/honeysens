#!/usr/bin/env sh
# Create links to either mounted or self-signed TLS certificate/key
rm -f /srv/tls/https.crt /srv/tls/https.key
if [ -s /srv/tls/server.crt -a -s /srv/tls/server.key ]; then
  # Mounted
  echo "Using mounted TLS cert/key"
  ln -s /srv/tls/server.crt /srv/tls/https.crt
  ln -s /srv/tls/server.key /srv/tls/https.key
else
  # Self-signed
  echo "Using self-signed TLS cert/key"
  ln -s /srv/data/https.chain.crt /srv/tls/https.crt
  ln -s /srv/data/https.key /srv/tls/https.key
fi

celery -A processor.processor worker -B -s /srv/data/tasks/celerybeat-schedule -l info -Q high,low -Ofair --prefetch-multiplier=1 --hsconfig=/srv/data/config.cfg
