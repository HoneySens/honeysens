#!/usr/bin/env sh

# Install requirements
apk --update --no-cache add --virtual build-dependencies build-base curl zeromq-dev libffi-dev libressl-dev postgresql-dev python3-dev
apk --update --no-cache add python3 zeromq py3-pip

# Install herlading from git
curl -s -L https://github.com/johnnykv/heralding/archive/Release_1.0.7.tar.gz -o /root/heralding.tar.gz
tar -xzf /root/heralding.tar.gz -C /root/ --strip-components 1
mv /root/honeysens_logger.py /root/heralding/reporting/

# Disabling default file-logging
(cd /root/heralding; patch -u heralding.yml -i /root/heralding.patch; patch -u honeypot.py -i /root/honeypot.patch)

printf "\ncryptography==3.3.1" >> /root/requirements.txt
(cd /root/; pip install --user --no-cache-dir -r /root/requirements.txt; python3 /root/setup.py install --user)

# cleanup
(cd /root/; rm -rf prepare.sh heralding.patch honeypot.patch heralding.tar.gz)
apk del build-dependencies
rm -rf /tmp/* /var/tmp/* /var/cache/apk/*
