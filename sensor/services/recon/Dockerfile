FROM multiarch/alpine:amd64-v3.7
MAINTAINER Pascal Brueckner pascal.brueckner@tu-dresden.de

COPY prepare.sh recon.py /opt/
RUN /bin/sh /opt/prepare.sh

ENTRYPOINT ["/usr/bin/python", "/opt/recon.py"]
