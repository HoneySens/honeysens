#!/usr/bin/env bash
set -e

NET_ADDR=$(ip -o -f inet addr show | awk '/eth0/ {print $4}')

# Consumes and acts on events from hostapd.
if [[ "$2" == "AP-STA-CONNECTED" ]]; then
    if ! grep -q "Allow ${NET_ADDR}" /srv/tinyproxy.conf; then
        echo "Enabling proxy access for ${NET_ADDR}"
        echo "Allow ${NET_ADDR}" >> /srv/tinyproxy.conf
        sv restart tinyproxy
    fi
fi

if [[ "$2" == "AP-STA-DISCONNECTED" ]]; then
    if grep -q "Allow ${NET_ADDR}" /srv/tinyproxy.conf; then
        sed -i -e "#Allow ${NET_ADDR}#d" /srv/tinyproxy.conf
        echo "Disabling proxy access for ${NET_ADDR}"
    fi
fi