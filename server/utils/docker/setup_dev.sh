#!/usr/bin/env bash
export DEBIAN_FRONTEND=noninteractive

# Additional dev requirements
apt-get install -y npm nodejs-legacy wget unzip texlive-base texlive-latex-extra texlive-extra-utils figlet boxes
npm install -g grunt-cli

# Create a DB template to initialize an empty DB volume unter /var/lib/mysql
mkdir -p /opt/HoneySens/templates /opt/HoneySens/data
touch /opt/HoneySens/data/config.cfg # Dummy to prevent the data initialization in 01_init_volumes.sh
cp -var /var/lib/mysql /opt/HoneySens/templates/
