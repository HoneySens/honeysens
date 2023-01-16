FROM alpine:3.17

COPY evgen.py requirements.txt /opt/
RUN apk add py3-pip py3-cryptography python3-dev build-base; \
    pip3 install -r /opt/requirements.txt
ENTRYPOINT ["/opt/evgen.py"]