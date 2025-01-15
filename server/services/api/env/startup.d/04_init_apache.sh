#!/usr/bin/env bash
set -e

# Enable HTTPS depending on whether a TLS private key exists
# A key will be missing when a certificate without key was mounted.
if [[ ! -e /srv/tls/https.key ]]; then
  echo "Apache: No TLS private key, not serving HTTPS requests"
  a2dissite honeysens_ssl
  echo -e "Listen 8080" >/etc/apache2/ports.conf
else
  echo "Apache: Serving HTTPS requests"
  a2ensite honeysens_ssl
  echo -e "Listen 8080\nListen 8443" >/etc/apache2/ports.conf
fi

# HTTP endpoint selection: Either redirect to HTTPS or serve the API in plain HTTP
if [[ "$PLAIN_HTTP_API" = "true" || ! -e /srv/tls/https.key ]]; then
    echo "Apache: Serving plain HTTP API requests"
    a2dissite honeysens_http_redirect
    a2ensite honeysens_http_api
else
    echo "Apache: Redirecting HTTP requests to HTTPS"
    a2dissite honeysens_http_api
    a2ensite honeysens_http_redirect
fi

# Enable or disable access logging to stdout depending on the environment variable ACCESS_LOG
if [[ "$ACCESS_LOG" = "true" ]]; then
    echo "Apache access log: enabled"
    sed -i -e "s|#*CustomLog.*|CustomLog /dev/stdout combined|" /etc/apache2/sites-available/*.conf
    a2enconf other-vhosts-access-log
else
    echo "Apache access log: disabled"
    sed -i -e "s|#*CustomLog.*|#CustomLog /dev/stdout combined|" /etc/apache2/sites-available/*.conf
    a2disconf other-vhosts-access-log
fi

# TLS Protocol configuration (either force TLS 1.2+ or be a bit more liberal by default)
if [[ "$TLS_FORCE_12" = "true" ]]; then
    echo "Apache: Forcing TLS 1.2+"
    sed -i -e "s|SSLProtocol.*|SSLProtocol -All +TLSv1.2 +TLSv1.3|" /etc/apache2/sites-available/*.conf
else
    sed -i -e "s|SSLProtocol.*|SSLProtocol All -SSLv2 -SSLv3|" /etc/apache2/sites-available/*.conf
fi