#!/usr/bin/env sh
set -e

# Force usage of the git binary (fixes "Failed to mmap" errors when building cryptography crate)
export CARGO_NET_GIT_FETCH_WITH_CLI=true

# Install requirements
apk --update --no-cache add --virtual build-dependencies cargo curl gcc g++ git libffi-dev openssl-dev python3-dev rust
apk --update add bash py3-pip py3-pyzmq

# Install cowrie from git
curl -s -L https://github.com/cowrie/cowrie/archive/refs/tags/v2.4.0.tar.gz -o /root/cowrie.tar.gz
tar -xzf /root/cowrie.tar.gz -C /opt
sed -i "/packaging/d" /opt/cowrie-2.4.0/requirements.txt  # use system "packaging" package
mv -v /root/honeysens.py /opt/cowrie-2.4.0/src/cowrie/output/
mv -v /root/cowrie.cfg /opt/cowrie-2.4.0/etc/
mv -v /root/run.sh /opt/
pip3 install --no-cache-dir -r /opt/cowrie-2.4.0/requirements.txt

# Setup permissions
adduser -D -s /bin/sh cowrie cowrie
chmod -R 777 /opt/cowrie-2.4.0/var

# Clean up
apk del build-dependencies
rm -rf /var/cache/apk/* /root/* /root/.cargo /root/.cache
