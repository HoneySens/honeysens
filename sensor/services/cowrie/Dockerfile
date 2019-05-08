ARG ARCH=amd64
FROM multiarch/alpine:${ARCH}-v3.9
MAINTAINER Pascal Brueckner pascal.brueckner@sylence.cc

COPY prepare.sh dont_daemonize.patch honeysens.py cowrie.cfg /root/
RUN /bin/sh /root/prepare.sh

USER cowrie
EXPOSE 2222
ENTRYPOINT ["/opt/cowrie-1.6.0/bin/cowrie", "start"]