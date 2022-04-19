#!/usr/bin/env bash
set -e

a2ensite honeysens_ssl

# HTTP endpoint selection: Either redirect to HTTPS or serve the API in plain HTTP depending on env PLAIN_HTTP_API
if [[ "$PLAIN_HTTP_API" = "true" ]]; then
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