FROM alpine:3.17

COPY prepare.sh run.sh honeysens.py cowrie.cfg /root/
RUN /bin/sh /root/prepare.sh

USER cowrie
CMD ["/opt/run.sh"]
