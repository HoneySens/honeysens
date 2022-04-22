#!/bin/bash

TARGET="https"
SUBJECT="/CN=$DOMAIN"

# If "force" is given as additional parameter, certificate generation is forced
FORCE=${1:-no}

# Create links to either mounted or self-signed TLS certificates/keys that should be used for this deployment.
# Starting with server 2.4.0, custom certificates are mounted into /srv/tls, so that links point to either
#   /srv/tls/https.crt -> /opt/HoneySens/data/https.crt
#   /srv/tls/https.key -> /opt/HoneySens/data/https.key
# for a self-signed certificate/key pair or
#   /srv/tls/https.crt -> /srv/tls/server.crt
#   /srv/tls/https.key -> /srv/tls/server.key
# for a custom certificate/key pair mounted at /srv/tls/server.crt and /srv/tls/server.key or JUST
#   /srv/tls/https.crt -> /srv/tls/server.crt
# for a custom certificate pair mounted at /srv/tls/server.crt without a key, e.g. when running just the plain HTTP API
# behind a TLS proxy, in which case we just require the certificate for distribution to sensors.
rm -f /srv/tls/https.crt /srv/tls/https.key  # Remove links from last execution
if [[ -s /srv/tls/server.crt && -s /srv/tls/server.key ]]; then
  # Mounted cert/key pair
  echo "Using mounted TLS cert/key pair"
  ln -s /srv/tls/server.crt /srv/tls/https.crt
  ln -s /srv/tls/server.key /srv/tls/https.key
elif [[ -s /srv/tls/server.crt && ! -s /srv/tls/server.key ]]; then
  # Mounted cert without key
  echo "Using mounted TLS certificate without key"
  ln -s /srv/tls/server.crt /srv/tls/https.crt
else
  # Self-signed cert/key pair
  echo "Using self-signed TLS cert/key pair"
  ln -s /opt/HoneySens/data/${TARGET}.chain.crt /srv/tls/https.crt
  ln -s /opt/HoneySens/data/${TARGET}.key /srv/tls/https.key
fi

if [[ ! -s /opt/HoneySens/data/${TARGET}.key ]]; then
    echo "Generating new self-signed TLS key/cert pair"
    openssl genrsa -out /opt/HoneySens/data/${TARGET}.key 2048
    openssl req -new -key /opt/HoneySens/data/${TARGET}.key -out /opt/HoneySens/data/${TARGET}.csr -subj "${SUBJECT}"
    openssl x509 -req -in /opt/HoneySens/data/${TARGET}.csr -CA /opt/HoneySens/data/CA/ca.crt -CAkey /opt/HoneySens/data/CA/ca.key -CAcreateserial -out /opt/HoneySens/data/${TARGET}.crt -days 365 -sha256 -extensions san -extfile <(printf "[san]\nsubjectAltName=DNS:${DOMAIN}")
    cat /opt/HoneySens/data/${TARGET}.crt /opt/HoneySens/data/CA/ca.crt > /opt/HoneySens/data/${TARGET}.chain.crt
elif [[ "$FORCE" = "force" ]]; then
    echo "Generating new self-signed TLS certificate for existing key"
    # Use subject line of existing certificate if one exists
    if [[ -e /opt/HoneySens/data/${TARGET}.crt ]]; then
      SUBJECT=$(openssl x509 -noout -subject -nameopt compat -in /opt/HoneySens/data/${TARGET}.crt | sed -e "s/subject=\(.*\)/\1/" | awk '{$1=$1};1')
      echo "  Re-using subject of existing certificate: ${SUBJECT}"
    fi
    openssl req -new -key /opt/HoneySens/data/${TARGET}.key -out /opt/HoneySens/data/${TARGET}.csr -subj "${SUBJECT}"
    openssl x509 -req -in /opt/HoneySens/data/${TARGET}.csr -CA /opt/HoneySens/data/CA/ca.crt -CAkey /opt/HoneySens/data/CA/ca.key -CAcreateserial -out /opt/HoneySens/data/${TARGET}.crt -days 365 -sha256 -extensions san -extfile <(printf "[san]\nsubjectAltName=DNS:${DOMAIN}")
    cat /opt/HoneySens/data/${TARGET}.crt /opt/HoneySens/data/CA/ca.crt > /opt/HoneySens/data/${TARGET}.chain.crt
fi
