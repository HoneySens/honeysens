FROM multiarch/alpine:amd64-v3.10

COPY prepare.sh honeysens_log.py rdpy-rdphoneypot.patch out.rss /root/
RUN /bin/sh /root/prepare.sh

USER rdpy
CMD ["/usr/bin/rdpy-rdphoneypot.py", "/opt/out.rss"]
