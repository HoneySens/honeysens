FROM multiarch/alpine:amd64-v3.10

COPY glastopf.cfg log_honeysens.py prepare.sh requirements.txt run.sh /root/
RUN /bin/sh /root/prepare.sh

CMD /opt/run.sh
