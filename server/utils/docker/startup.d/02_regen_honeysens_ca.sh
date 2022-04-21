#!/bin/bash
set -e

CA_SUBJECT="/CN=HoneySens"
CA_EXPIRATION_DAYS=365

# If "force" is given as additional parameter, certificate generation is forced
FORCE=${1:-no}

# Update OpenSSL CA config
if [[ -e /opt/HoneySens/templates/data/CA/openssl_ca.cnf ]]; then
    cp -va /opt/HoneySens/templates/data/CA/openssl_ca.cnf /opt/HoneySens/data/CA/
fi

# Check existing CA key size, remove old key/certs if invalid
if [[ -e /opt/HoneySens/data/CA/ca.key ]]; then
  CA_KEY_SIZE=$(openssl rsa -in /opt/HoneySens/data/CA/ca.key -text -noout | grep "Private-Key")
  if [[ ! "$CA_KEY_SIZE" =~ "2048 bit" ]]; then
    echo "Size of existing CA key too small, recreating certificate chain"
    rm -vf /opt/HoneySens/data/CA/ca.key /opt/HoneySens/data/CA/ca.crt /opt/HoneySens/data/CA/ca.srl /opt/HoneySens/data/https.chain.crt /opt/HoneySens/data/https.crt /opt/HoneySens/data/https.csr /opt/HoneySens/data/https.key
    if [[ ! -s /srv/tls/server.crt && ! -s /srv/tls/server.key ]]; then
      echo "Self-signed TLS certificates are active, ALL sensors need to be reinstalled after this update!"
    fi
  fi
fi

# Generate a new CA if none was found
if [[ ! -e /opt/HoneySens/data/CA/ca.key ]]; then
  echo "Generating new CA key and certificate with subject ${CA_SUBJECT}"
  openssl req -nodes -new -x509 -extensions v3_ca -keyout /opt/HoneySens/data/CA/ca.key -out /opt/HoneySens/data/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config /opt/HoneySens/data/CA/openssl_ca.cnf -subj "${CA_SUBJECT}"
elif [[ "$FORCE" = "force" ]]; then
  # Use subject line of existing certificate
  if [[ -e /opt/HoneySens/data/CA/ca.crt ]]; then
    CA_SUBJECT=$(openssl x509 -noout -subject -nameopt compat -in /opt/HoneySens/data/CA/ca.crt | sed -e "s/subject=\(.*\)/\1/" | awk '{$1=$1};1')
  fi
  echo "Generating new CA certificate for existing key using CA subject ${CA_SUBJECT}"
  openssl req -nodes -new -x509 -extensions v3_ca -key /opt/HoneySens/data/CA/ca.key -out /opt/HoneySens/data/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config /opt/HoneySens/data/CA/openssl_ca.cnf -subj "${CA_SUBJECT}"
fi
