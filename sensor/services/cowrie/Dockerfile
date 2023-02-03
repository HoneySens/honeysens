ARG ARCH=amd64
FROM multiarch/alpine:${ARCH}-v3.14

COPY prepare.sh run.sh honeysens.py cowrie.cfg /root/
RUN /bin/sh /root/prepare.sh

USER cowrie
CMD ["/opt/run.sh"]
