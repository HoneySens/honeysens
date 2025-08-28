#!/usr/bin/env bash
set -e
# Basic container initialization for both productive and development environments

# Basic requirements
apk --update --no-cache add --virtual build-dependencies alpine-sdk python3-dev linux-headers zeromq-dev libffi-dev yaml-dev
apk --update --no-cache add ca-certificates cntlm curl curl-dev dhcpcd docker docker-compose libffi libpcap macchanger py3-cryptography py3-netifaces py3-pip tar yaml wpa_supplicant zeromq

# Overlay s6 init system
S6_VERSION="2.2.0.3"
echo "Building with s6 version: ${S6_VERSION}"
curl -o /tmp/s6-overlay-amd64.tar.gz -sL https://github.com/just-containers/s6-overlay/releases/download/v${S6_VERSION}/s6-overlay-amd64.tar.gz
tar xzf /tmp/s6-overlay-amd64.tar.gz -C /

# Ensure the existence of /etc/network/interfaces
touch /etc/network/interfaces

# Shadow /sbin/ifup with a decoy that also accepts --no-act as -n (required by python's debinterface)
mv /sbin/ifup /usr/local/bin/ifup
mv /srv/ifup.sh /sbin/ifup

# Expose sensor manager CLI
ln -s /srv/manager/venv/bin/manager-cli /usr/local/bin/manager-cli

# Set revision marker
echo $1 > /revision