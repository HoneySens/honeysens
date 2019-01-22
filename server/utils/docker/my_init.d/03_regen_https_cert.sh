#!/bin/bash

TARGET="https"
SUBJECT="/CN=$(hostname)"

# If "force" is given as additional parameter, certificate generation is forced
FORCE=${1:-no}

# Server versions < 0.9.0 were using /opt/HoneySens/data/ssl-cert.key and /opt/HoneySens/data/ssl-cert.pem
if [[ -e /opt/HoneySens/data/ssl-cert.key ]] && [[ -e /opt/HoneySens/data/ssl-cert.pem ]]; then
    echo "TLS certificate from server < 0.9.0 present, attempting import..."
    # If an old certificate pair exists and actually contains data, import it. Otherwise, remove the leftover files.
    # Zero-size dummy files remain if files were bind mounted as volumes into an container previously.
    if [[ -s /opt/HoneySens/data/ssl-cert.key ]] && [[ -s /opt/HoneySens/data/ssl-cert.pem ]]; then
        echo "Importing TLS certificate from server < 0.9.0"
        # Add another fallback layer: if importing the key fails, https.key is currently mounted as a volume and can't be overwritten.
        # In that case the import process is skipped.
        if mv -v /opt/HoneySens/data/ssl-cert.key /opt/HoneySens/data/https.key; then
            mv -v /opt/HoneySens/data/ssl-cert.pem /opt/HoneySens/data/https.crt
            cat /opt/HoneySens/data/https.crt /opt/HoneySens/data/CA/ca.crt > /opt/HoneySens/data/https.chain.crt
        else
            echo "Skipping import, custom certificate mounted"
        fi
    else
        echo "No import required, removing leftover certificate dummys"
        rm -v /opt/HoneySens/data/ssl-cert.key /opt/HoneySens/data/ssl-cert.pem
    fi
fi

# Figure out whether a custom certificate was submitted as a bind mount, in which case we can safely quit
findmnt -n -M /opt/HoneySens/data/https.key >/dev/null
if [[ "$?" == "0" ]]; then
    echo "Bind-mounted TLS certificate found, not generating a new one"
    exit 0
fi

if [[ ! -e /opt/HoneySens/data/${TARGET}.key ]]; then
    echo "Generating new TLS key pair"
    openssl genrsa -out /opt/HoneySens/data/${TARGET}.key 2048
    openssl req -new -key /opt/HoneySens/data/${TARGET}.key -out /opt/HoneySens/data/${TARGET}.csr -subj "${SUBJECT}"
    openssl x509 -req -in /opt/HoneySens/data/${TARGET}.csr -CA /opt/HoneySens/data/CA/ca.crt -CAkey /opt/HoneySens/data/CA/ca.key -CAcreateserial -out /opt/HoneySens/data/${TARGET}.crt -days 365 -sha256
    cat /opt/HoneySens/data/${TARGET}.crt /opt/HoneySens/data/CA/ca.crt > /opt/HoneySens/data/${TARGET}.chain.crt
elif [[ "$FORCE" = "force" ]]; then
    echo "Generating new TLS certificate for existing key"
    # Use subject line of existing certificate if one exists
    if [[ -e /opt/HoneySens/data/${TARGET}.crt ]]; then
      SUBJECT=$(openssl x509 -noout -subject -in /opt/HoneySens/data/${TARGET}.crt | sed -e "s/subject=\(.*\)/\1/" | awk '{$1=$1};1')
      echo "  Re-using subject of existing certificate: ${SUBJECT}"
    fi
    openssl req -new -key /opt/HoneySens/data/${TARGET}.key -out /opt/HoneySens/data/${TARGET}.csr -subj "${SUBJECT}"
    openssl x509 -req -in /opt/HoneySens/data/${TARGET}.csr -CA /opt/HoneySens/data/CA/ca.crt -CAkey /opt/HoneySens/data/CA/ca.key -CAcreateserial -out /opt/HoneySens/data/${TARGET}.crt -days 365 -sha256
    cat /opt/HoneySens/data/${TARGET}.crt /opt/HoneySens/data/CA/ca.crt > /opt/HoneySens/data/${TARGET}.chain.crt
fi
