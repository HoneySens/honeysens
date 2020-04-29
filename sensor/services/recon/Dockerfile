FROM multiarch/alpine:amd64-v3.10

COPY prepare.sh recon.py /opt/
RUN /bin/sh /opt/prepare.sh

ENTRYPOINT ["/usr/bin/python", "/opt/recon.py"]
