FROM ubuntu:22.04

COPY cmake.patch config.patch log_honeysens.py log_honeysens.yaml.in prepare.sh /root/
RUN /bin/sh /root/prepare.sh

CMD ["/opt/dionaea/bin/dionaea"]
