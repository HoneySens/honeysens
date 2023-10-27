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

if [[ "${HS_WORKER_COUNT}" == "auto" ]]; then
  echo "Workers: auto (# of CPU cores)"
  export WORKERS=""
else
  echo "Workers: ${HS_WORKER_COUNT}"
  export WORKERS="-c ${HS_WORKER_COUNT}"
fi

exec supervisord
