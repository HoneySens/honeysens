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

if [[ "${HS_WORKER_COUNT}" == "auto" ]]; then
  echo "Workers: auto (# of CPU cores)"
  export WORKERS=""
else
  echo "Workers: ${HS_WORKER_COUNT}"
  export WORKERS="-c ${HS_WORKER_COUNT}"
fi

# Install and run task processor via supervisord
export PYTHONDONTWRITEBYTECODE=1
pip3 install -e /mnt
rm -vr /mnt/*.egg-info
exec supervisord -c /etc/supervisord.conf
