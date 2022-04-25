#!/usr/bin/env sh
# Create links to either mounted or self-signed TLS certificate/key
rm -f /srv/tls/https.crt /srv/tls/https.key
if [[ -s /srv/tls/server.crt && -s /srv/tls/server.key ]]; then
  # Mounted cert/key pair
  echo "Using mounted TLS cert/key pair"
  ln -s /srv/tls/server.crt /srv/tls/https.crt
  ln -s /srv/tls/server.key /srv/tls/https.key
elif [[ -s /srv/tls/server.crt && ! -s /srv/tls/server.key ]]; then
  # Mounted cert without key
  echo "Using mounted TLS certificate without key"
  ln -s /srv/tls/server.crt /srv/tls/https.crt
elif [[ -d /srv/tls/server && -s /srv/tls/server/tls.crt && -s /srv/tls/server/tls.key ]]; then
  # Mounted cert/key pair inside directory
  echo "Using TLS cert/key pair mounted inside subdirectory"
  ln -s /srv/tls/server/tls.crt /srv/tls/https.crt
  ln -s /srv/tls/server/tls.key /srv/tls/https.key
else
  # Self-signed cert/key pair
  echo "Using self-signed TLS cert/key pair"
  ln -s /srv/data/https.chain.crt /srv/tls/https.crt
  ln -s /srv/data/https.key /srv/tls/https.key
fi

celery -A processor.processor worker -B -s /srv/data/tasks/celerybeat-schedule -l info -Q high,low -Ofair --prefetch-multiplier=1 --hsconfig=/srv/data/config.cfg
