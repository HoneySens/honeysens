#!/usr/bin/env bash
set -e

if [[ "$EAPOL_ENABLED" == "true" ]]; then
    # EAPOL enabled, remove any "Allow" statements from the tinyproxy config except for localhost
    sed -i -e "/Allow/d" /srv/tinyproxy.conf
    echo "Allow 127.0.0.1" >> /srv/tinyproxy.conf
    rm -f /etc/service/hostapd/down /etc/service/hostapd_listener/down
    echo "EAPOL authentication is enabled"
else
    # EAPOL is disabled, remove any specific "Allow" statements from the tinyproxy config (to enable a generic ALLOW)
    sed -i -e "/Allow/d" /srv/tinyproxy.conf
    touch /etc/service/hostapd/down /etc/service/hostapd_listener/down
    echo "EAPOL authentication is disabled"
fi
