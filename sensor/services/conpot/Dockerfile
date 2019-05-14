ARG ARCH=amd64
FROM multiarch/alpine:${ARCH}-v3.9

COPY prepare.sh honeysens_log.py config.patch log_worker.patch /root/
COPY http /root/http/
RUN /bin/sh /root/prepare.sh

CMD ["/usr/bin/conpot", "-c", "/srv/conpot.cfg", "--template", "default"]
