#!/bin/bash
set -e

# Always enable the HTTP default site
a2ensite honeysens_http
# Enable TLS vhost
echo "Enabling default TLS vhost configuration"
a2ensite honeysens_ssl
