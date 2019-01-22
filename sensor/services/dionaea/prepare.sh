#!/usr/bin/env sh

apt-get update
apt-get install -y git build-essential cmake check cython3 libcurl4-openssl-dev libemu-dev libev-dev libglib2.0-dev libloudmouth1-dev libnetfilter-queue-dev libnl-3-dev libpcap-dev libssl-dev libtool libudns-dev python3 python3-dev python3-bson python3-yaml ttf-liberation python3-zmq

git clone https://github.com/DinoTools/dionaea.git /root/dionaea
(cd /root/dionaea; git checkout 93d5bf93d03143c489069bf93216854b44a7d703)

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
rm -v /opt/dionaea/etc/dionaea/services-enabled/sip.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/tftp.yaml
rm -v /opt/dionaea/etc/dionaea/services-enabled/upnp.yaml
# Update main configuration
patch -d /opt/dionaea -p1 < /root/config.patch
ln -vs ../ihandlers-available/log_honeysens.yaml /opt/dionaea/etc/dionaea/ihandlers-enabled/log_honeysens.yaml

# Cleanup
apt-get remove -y build-essential cmake git check
apt-get autoremove -y
apt-get remove -y "*-dev"
rm -r /var/cache/apt/* /root/dionaea
