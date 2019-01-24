#!/usr/bin/env sh

cd /root
# libdnet
wget https://github.com/dugsong/libdnet/archive/libdnet-1.12.tar.gz
tar xzf libdnet-1.12.tar.gz
cd /root/libdnet-libdnet-1.12
./configure --prefix=/usr/local
make
make install
#cp ./include/dnet/sctp.h /usr/local/include/dnet/
# libevent1
cd /root
wget https://github.com/libevent/libevent/archive/release-1.4.15-stable.tar.gz
tar xzf release-1.4.15-stable.tar.gz
cd /root/libevent-release-1.4.15-stable
./autogen.sh
./configure --prefix=/usr/local
make
make install
# arpd
cd /root
wget http://www.citi.umich.edu/u/provos/honeyd/arpd-0.2.tar.gz
tar xzf arpd-0.2.tar.gz
cd /root/arpd
patch -p1 < /root/arpd.diff
./configure --prefix=/usr/local
make
make install
