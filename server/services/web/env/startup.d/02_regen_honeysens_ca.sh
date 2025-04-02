#!/bin/bash
set -e

CA_SUBJECT="/CN=HoneySens"
CA_EXPIRATION_DAYS=365

# If "force" is given as additional parameter, certificate generation is forced
FORCE=${1:-no}

# Check existing CA key size, remove old key/certs if invalid
if [[ -e ${HS_DATA_PATH}/CA/ca.key ]]; then
  CA_KEY_SIZE=$(openssl rsa -in ${HS_DATA_PATH}/CA/ca.key -text -noout | grep "Private-Key")
  if [[ ! "$CA_KEY_SIZE" =~ "2048 bit" ]]; then
    echo "Size of existing CA key too small, recreating certificate chain"
    rm -vf ${HS_DATA_PATH}/CA/ca.key ${HS_DATA_PATH}/CA/ca.crt ${HS_DATA_PATH}/CA/ca.srl ${HS_DATA_PATH}/https.chain.crt ${HS_DATA_PATH}/https.crt ${HS_DATA_PATH}/https.csr ${HS_DATA_PATH}/https.key
    if [[ ! -s /srv/tls/server.crt && ! -s /srv/tls/server.key && ! -s /srv/tls/server/tls.crt && ! -s /srv/tls/server/tls.key ]]; then
      echo "Self-signed TLS certificates are active, ALL sensors need to be reinstalled after this update!"
    fi
  fi
fi

# Generate a new CA if none was found
if [[ ! -e ${HS_DATA_PATH}/CA/ca.key ]]; then
  echo "Generating new CA key and certificate with subject ${CA_SUBJECT}"
  openssl req -nodes -new -x509 -extensions v3_ca -keyout ${HS_DATA_PATH}/CA/ca.key -out ${HS_DATA_PATH}/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config ${HS_DATA_PATH}/CA/openssl_ca.cnf -subj "${CA_SUBJECT}"
elif [[ "$FORCE" = "force" ]]; then
  # Use subject line of existing certificate
  if [[ -e ${HS_DATA_PATH}/CA/ca.crt ]]; then
    CA_SUBJECT=$(openssl x509 -noout -subject -nameopt compat -in ${HS_DATA_PATH}/CA/ca.crt | sed -e "s/subject=\(.*\)/\1/" | awk '{$1=$1};1')
  fi
  echo "Generating new CA certificate for existing key using CA subject ${CA_SUBJECT}"
  openssl req -nodes -new -x509 -extensions v3_ca -key ${HS_DATA_PATH}/CA/ca.key -out ${HS_DATA_PATH}/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config ${HS_DATA_PATH}/CA/openssl_ca.cnf -subj "${CA_SUBJECT}"
fi
