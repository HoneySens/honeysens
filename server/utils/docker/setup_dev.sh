#!/usr/bin/env bash
set -e
export DEBIAN_FRONTEND=noninteractive

# Additional dev requirements
apt-get install -y boxes figlet git nodejs npm unzip wget
npm install -g grunt-cli

# Create dummy template config to prevent data initialization in 01_init_volumes.sh
mkdir -p /opt/HoneySens/data
touch /opt/HoneySens/data/config.cfg
chown -R ubuntu:ubuntu /opt/HoneySens

# Disable LDAP certificate verification within the dev environment
echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf

# Permit write access to startup scripts
chown ubuntu:ubuntu /etc/startup.d

# Create composer shortcut in $PATH
echo -e '#!/usr/bin/env bash\n/mnt/out/dev/composer.phar -d /mnt/out/dev/ $*' >/usr/local/bin/composer
chmod +x /usr/local/bin/composer
