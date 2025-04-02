#!/usr/bin/env bash
set -e

# Uses OpenSSL to create a self-signed CA and both signed server and client certificates that can be used for testing
echo "Generating CA private key"
openssl genrsa -out ca.key 4096

echo "Creating self-signed CA certificate"
openssl req -x509 -new -extensions v3_ca -key ca.key -out ca.crt -subj "/C=US/ST=CA/O=Example/CN=example.com"

echo "Generating server private key"
openssl genrsa -out server.key 4096

echo "Creating server certificate"
openssl req -new -key server.key -out server.csr -subj "/C=US/ST=CA/O=Example/CN=server.example.com"
openssl x509 -req -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out server.crt

echo "Generating client private key"
openssl genrsa -out client.key 4096

echo "Creating client certificate"
openssl req -new -key client.key -out client.csr -subj "/C=US/ST=CA/O=Example/CN=client.example.com"
openssl x509 -req -in client.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out client.crt

echo "Adjusting private key permissions"
chmod a+r ca.key server.key client.key