#!/bin/bash
set -e

CA_SUBJECT="/CN=HoneySens"
CA_EXPIRATION_DAYS=365

# If "force" is given as additional parameter, certificate generation is forced
FORCE=${1:-no}

# Server versions < 0.9.0 were using /opt/HoneySens/data/CA/cakey.pem and /opt/HoneySens/data/CA/cacert.pem
if [[ -e /opt/HoneySens/data/CA/cakey.pem ]] && [[ -e /opt/HoneySens/data/CA/cacert.pem ]]; then
    echo "Importing CA from server < 0.9.0"
    mv -v /opt/HoneySens/data/CA/cakey.pem /opt/HoneySens/data/CA/ca.key
    mv -v /opt/HoneySens/data/CA/cacert.pem /opt/HoneySens/data/CA/ca.crt
    mv -v /opt/HoneySens/data/CA/openssl.cnf /opt/HoneySens/data/CA/openssl_ca.cnf
    # Re-sign the legacy CA cert with its original parameters so that it stays compatible with already issued client certs
    CA_SUBJECT=$(openssl x509 -noout -subject -in /opt/HoneySens/data/CA/ca.crt | sed -e "s/subject=\(.*\)/\1/" | awk '{$1=$1};1')
    echo "  Found CA subject: ${CA_SUBJECT}"
    openssl req -nodes -extensions v3_ca -new -x509 -key /opt/HoneySens/data/CA/ca.key -out /opt/HoneySens/data/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config /opt/HoneySens/data/CA/openssl_ca.cnf -subj "${CA_SUBJECT}" -passin pass:asdf
fi

# Update OpenSSL CA config
if [[ -e /opt/HoneySens/templates/data/CA/openssl_ca.cnf ]]; then
    cp -v /opt/HoneySens/templates/data/CA/openssl_ca.cnf /opt/HoneySens/data/CA/
fi

# Generate a new CA if none was found
if [[ ! -e /opt/HoneySens/data/CA/ca.key ]]; then
  echo "Generating new CA key and certificate with subject ${CA_SUBJECT}"
  openssl req -nodes -new -x509 -extensions v3_ca -keyout /opt/HoneySens/data/CA/ca.key -out /opt/HoneySens/data/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config /opt/HoneySens/data/CA/openssl_ca.cnf -subj "${CA_SUBJECT}"
elif [[ "$FORCE" = "force" ]]; then
  # Use subject line of existing certificate
  if [[ -e /opt/HoneySens/data/CA/ca.crt ]]; then
    CA_SUBJECT=$(openssl x509 -noout -subject -in /opt/HoneySens/data/CA/ca.crt | sed -e "s/subject=\(.*\)/\1/" | awk '{$1=$1};1')
  fi
  echo "Generating new CA certificate for existing key using CA subject ${CA_SUBJECT}"
  openssl req -nodes -new -x509 -extensions v3_ca -key /opt/HoneySens/data/CA/ca.key -out /opt/HoneySens/data/CA/ca.crt -days ${CA_EXPIRATION_DAYS} -config /opt/HoneySens/data/CA/openssl_ca.cnf -subj "${CA_SUBJECT}"
fi