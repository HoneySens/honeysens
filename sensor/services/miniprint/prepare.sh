#!/usr/bin/env sh
apk --update --no-cache add --virtual build-dependencies build-base git linux-headers python3-dev py3-pip python2-dev zeromq-dev
apk --update --no-cache add python3 zeromq 

git clone https://github.com/sa7mon/miniprint.git /root/miniprint
(cd /root/miniprint/; git checkout b5c8aa4f990869d22a00a054f1a08edf12502a1f; mv /root/server.patch /root/miniprint/; git apply server.patch)
mv -v /root/miniprint/* /app/
mv -v /root/honeysens.py /app/
pip install --no-cache-dir -r requirements.txt
pip install --no-cache-dir pyzmq
rm -rf /tmp/* /var/tmp/* /var/cache/apk/* /root/* test_printer.py requirements.txt readme.md LICENSE.md server.patch
apk del build-dependencies
