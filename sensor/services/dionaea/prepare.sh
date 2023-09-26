#!/usr/bin/env sh
set -e

apt update
apt install -y git build-essential cmake check curl cython3 libcurl4-openssl-dev libev-dev libglib2.0-dev libloudmouth1-dev libnetfilter-queue-dev libnl-3-dev libnl-route-3-dev libpcap-dev libssl-dev libtool libudns-dev python3 python3-dev python3-bson python3-yaml fonts-liberation python3-zmq

# Install libemu from source
curl -s -L https://github.com/buffer/libemu/archive/refs/tags/v1.0.5.tar.gz -o /root/libemu.tar.gz
tar -xzf /root/libemu.tar.gz -C /root
autoreconf -v -i /root/libemu-1.0.5/
(cd /root/libemu-1.0.5; ./configure && make install)

# Install dionaea from source
git clone https://github.com/DinoTools/dionaea.git /root/dionaea
(cd /root/dionaea; git checkout 4e459f1b672a5b4c1e8335c0bff1b93738019215)

# Patch sources
mv -v /root/log_honeysens.yaml.in /root/dionaea/conf/ihandlers/
mv -v /root/log_honeysens.py /root/dionaea/modules/python/dionaea/
patch -d /root/dionaea -p1 < /root/cmake.patch

mkdir /root/dionaea/build
(cd /root/dionaea/build/; cmake -DCMAKE_INSTALL_PREFIX:PATH=/opt/dionaea ..)
make -C /root/dionaea/build/ install

# Disable unwanted incident handlers
rm -v /opt/dionaea/etc/dionaea/ihandlers-enabled/ftp.yaml
rm -v /opt/dionaea/etc/dionaea/ihandlers-enabled/log_sqlite.yaml
rm -v /opt/dionaea/etc/dionaea/ihandlers-enabled/store.yaml
rm -v /opt/dionaea/etc/dionaea/ihandlers-enabled/tftp_download.yaml
# Disable unwanted services
rm -v /opt/dionaea/etc/dionaea/services-enabled/blackhole.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/epmap.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/ftp.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/http.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/memcache.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/mirror.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/mongo.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/mqtt.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/mssql.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/mysql.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/pptp.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/printer.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/sip.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/tftp.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/upnp.yaml
# Update main configuration
patch -d /opt/dionaea -p1 < /root/config.patch
ln -vs ../ihandlers-available/log_honeysens.yaml /opt/dionaea/etc/dionaea/ihandlers-enabled/log_honeysens.yaml

# Cleanup
apt remove -y build-essential cmake curl git check
apt autoremove -y
apt remove -y "*-dev"
rm -r /var/cache/apt/* /root/dionaea /root/libemu-1.0.5 /root/libemu.tar.gz
