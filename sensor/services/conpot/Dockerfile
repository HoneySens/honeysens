FROM multiarch/alpine:amd64-v3.7

COPY prepare.sh honeysens_log.py config.patch log_worker.patch /root/
RUN /bin/sh /root/prepare.sh

CMD ["/usr/bin/conpot", "-c", "/srv/conpot.cfg", "--template", "default"]
