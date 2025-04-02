#!/bin/bash
set -e

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
# for a custom certificate mounted at /srv/tls/server.crt without a key, e.g. when running just the plain HTTP API
# behind a TLS proxy, in which case we just require the certificate for distribution to sensors or
#   /srv/tls/https.crt -> /srv/tls/server/tls.crt
#   /srv/tls/https.key -> /srv/tls/server/tls.key
# for a custom certificate/key pair mounted inside /srv/tls/server/
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
elif [[ -d /srv/tls/server && -s /srv/tls/server/tls.crt && -s /srv/tls/server/tls.key ]]; then
  # Mounted cert/key pair inside directory
  echo "Using TLS cert/key pair mounted inside subdirectory"
  ln -s /srv/tls/server/tls.crt /srv/tls/https.crt
  ln -s /srv/tls/server/tls.key /srv/tls/https.key
else
  # Self-signed cert/key pair
  echo "Using self-signed TLS cert/key pair"
  ln -s ${HS_DATA_PATH}/${TARGET}.chain.crt /srv/tls/https.crt
  ln -s ${HS_DATA_PATH}/${TARGET}.key /srv/tls/https.key
fi

if [[ ! -s ${HS_DATA_PATH}/${TARGET}.key ]]; then
    echo "Generating new self-signed TLS key/cert pair"
    openssl genrsa -out ${HS_DATA_PATH}/${TARGET}.key 2048
    openssl req -new -key ${HS_DATA_PATH}/${TARGET}.key -out ${HS_DATA_PATH}/${TARGET}.csr -subj "${SUBJECT}"
    openssl x509 -req -in ${HS_DATA_PATH}/${TARGET}.csr -CA ${HS_DATA_PATH}/CA/ca.crt -CAkey ${HS_DATA_PATH}/CA/ca.key -CAcreateserial -out ${HS_DATA_PATH}/${TARGET}.crt -days 365 -sha256 -extensions san -extfile <(printf "[san]\nsubjectAltName=DNS:${DOMAIN}")
    cat ${HS_DATA_PATH}/${TARGET}.crt ${HS_DATA_PATH}/CA/ca.crt > ${HS_DATA_PATH}/${TARGET}.chain.crt
elif [[ "$FORCE" = "force" ]]; then
    echo "Generating new self-signed TLS certificate for existing key"
    openssl req -new -key ${HS_DATA_PATH}/${TARGET}.key -out ${HS_DATA_PATH}/${TARGET}.csr -subj "${SUBJECT}"
    openssl x509 -req -in ${HS_DATA_PATH}/${TARGET}.csr -CA ${HS_DATA_PATH}/CA/ca.crt -CAkey ${HS_DATA_PATH}/CA/ca.key -CAcreateserial -out ${HS_DATA_PATH}/${TARGET}.crt -days 365 -sha256 -extensions san -extfile <(printf "[san]\nsubjectAltName=DNS:${DOMAIN}")
    cat ${HS_DATA_PATH}/${TARGET}.crt ${HS_DATA_PATH}/CA/ca.crt > ${HS_DATA_PATH}/${TARGET}.chain.crt
fi
