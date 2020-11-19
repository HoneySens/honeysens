#!/usr/bin/env sh

# Install requirements
apk --update --no-cache add python3 python3-dev py3-pip postgresql-dev zeromq zeromq-dev curl build-base libffi-dev libressl-dev

# Install herlading from git
curl -s -L https://github.com/johnnykv/heralding/archive/Release_1.0.7.tar.gz -o /root/heralding.tar.gz
tar -xzf /root/heralding.tar.gz -C /root/ --strip-components 1 
mv /root/honeysens_logger.py /root/heralding/reporting/

# Disabling default file-logging
(cd /root/heralding; patch -u heralding.yml -i /root/heralding.patch; patch -u honeypot.py -i /root/honeypot.patch)

(cd /root/; pip install --user --no-cache-dir -r /root/requirements.txt; python3 /root/setup.py install --user)

# cleanup
(cd /root/; rm -rf prepare.sh heralding.patch honeypot.patch heralding.tar.gz)
rm -rf /tmp/* /var/tmp/* /var/cache/apk/*
