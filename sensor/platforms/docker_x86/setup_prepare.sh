#!/usr/bin/env bash
set -e
# Basic container initialization for both productive and development environments

# Basic requirements
apk --update --no-cache add --virtual build-dependencies alpine-sdk python3-dev linux-headers zeromq-dev libffi-dev yaml-dev
apk --update --no-cache add ca-certificates cntlm curl-dev dhcpcd docker docker-compose libffi libpcap macchanger py3-pip tar yaml wpa_supplicant zeromq

# Overlay s6 init system
S6_VERSION="2.2.0.3"
echo "Building with s6 version: ${S6_VERSION}"
curl -o /tmp/s6-overlay-amd64.tar.gz -sL https://github.com/just-containers/s6-overlay/releases/download/v${S6_VERSION}/s6-overlay-amd64.tar.gz
tar xzf /tmp/s6-overlay-amd64.tar.gz -C /

# Install arpd and honeyd dependencies
# apk --no-cache add libevent-dev libdnet-dev libpcap-dev pcre-dev libedit-dev automake autoconf zlib-dev libtool

# Ensure the existence of /etc/network/interfaces
touch /etc/network/interfaces

# Shadow /sbin/ifup with a decoy that also accepts --no-act as -n (required by python's debinterface)
mv /sbin/ifup /usr/local/bin/ifup
mv /opt/ifup.sh /sbin/ifup

# Set revision marker
echo $1 > /revision

# Build honeyd
#cd /root
#git clone https://github.com/DataSoft/honeyd
#cd /root/honeyd
#patch -p1 < /opt/honeyd.diff
#./autogen.sh
#./configure
#make
#make install

# Build arpd
#cd /root
## libdnet
#wget https://github.com/dugsong/libdnet/archive/libdnet-1.12.tar.gz
#tar xzf libdnet-1.12.tar.gz
#cd /root/libdnet-libdnet-1.12
#./configure --prefix=/usr/local
#make
#make install
##cp ./include/dnet/sctp.h /usr/local/include/dnet/
## libevent1
#cd /root
#wget https://github.com/libevent/libevent/archive/release-1.4.15-stable.tar.gz
#tar xzf release-1.4.15-stable.tar.gz
#cd /root/libevent-release-1.4.15-stable
#./autogen.sh
#./configure --prefix=/usr/local
#make
#make install
## arpd
#cd /root
#wget http://www.citi.umich.edu/u/provos/honeyd/arpd-0.2.tar.gz
#tar xzf arpd-0.2.tar.gz
#cd /root/arpd
#patch -p1 < /opt/arpd.diff
#./configure --prefix=/usr/local
#make
#make install
