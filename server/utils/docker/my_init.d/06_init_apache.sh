#!/bin/bash
set -e

# Always enable the HTTP default site
a2ensite honeysens_http

# Use the default apache vhost if the environment variable TLS_AUTH_PROXY is not set,
# otherwise enable one that accepts authentication headers from a proxy which performs client authentication on our behalf.
# See: http://www.zeitoun.net/articles/client-certificate-x509-authentication-behind-reverse-proxy/start
if [[ -z "$TLS_AUTH_PROXY" ]]; then
    echo "Enabling default SSL vhost configuration"
    a2ensite honeysens_ssl
else
    echo "Enabling auth proxy vhost configuration for $TLS_AUTH_PROXY"
    sed -i -e "s/SetEnvIf Remote_Addr \".*\" X-SSL-PROXY-AUTH=true/SetEnvIf Remote_Addr \"$TLS_AUTH_PROXY\" X-SSL-PROXY-AUTH=true/" /etc/apache2/sites-available/honeysens_ssl_proxy_auth.conf
    a2ensite honeysens_ssl_proxy_auth
fi
