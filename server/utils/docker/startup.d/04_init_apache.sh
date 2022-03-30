#!/usr/bin/env bash
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

# TLS Protocol configuration (either force TLS 1.2 or be a bit more liberal by default)
if [[ "$TLS_FORCE_12" = "true" ]]; then
    echo "Apache: Forcing TLS 1.2"
    sed -i -e "s|SSLProtocol.*|SSLProtocol -All +TLSv1.2|" /etc/apache2/sites-available/*.conf
else
    # We currently disable TLSv1.3 because client cert auth with renegotiation is currently not supported in all browsers
    # and leads to issues when attempting to download firmware directly from the web app.
    # Reference: https://bugs.launchpad.net/ubuntu/+source/firefox/+bug/1834671
    sed -i -e "s|SSLProtocol.*|SSLProtocol All -TLSv1.3 -SSLv2 -SSLv3|" /etc/apache2/sites-available/*.conf
fi