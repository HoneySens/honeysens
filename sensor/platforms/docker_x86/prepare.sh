#!/usr/bin/env sh

# Build honeyd
#cd /root
#git clone https://github.com/DataSoft/honeyd
#cd /root/honeyd
#patch -p1 < /root/honeyd.diff
#./autogen.sh
#./configure
#make
#make install

# Docker Compose
pip install docker-compose

# Build sensor manager
mkdir /etc/manager
cd /opt/manager
python setup.py install

# Ensure the existence of /etc/network/interfaces
touch /etc/network/interfaces

# Shadow /sbin/ifup with a decoy that also accepts --no-act as -n (required by python's debinterface)
mv /sbin/ifup /usr/local/bin/ifup
mv /root/ifup.sh /sbin/ifup

# Revision marker
echo $1 > /revision