FROM alpine:3.16

ARG BID
# Defaults
ENV BUILD_ID=$BID \
    HS_BROKER_HOST=broker \
    HS_BROKER_PORT=6379 \
    HS_DB_HOST=database \
    HS_DB_PORT=3306 \
    HS_DB_USER=honeysens \
    HS_DB_NAME=honeysens \
    HS_DB_PASSWORD=secret \
    HS_REGISTRY_HOST=registry \
    HS_REGISTRY_PORT=5000

COPY processor /opt/processor/
COPY run.sh setup.py /opt/

RUN set -ex; \
    apk --no-cache upgrade; \
    apk --no-cache add py3-cryptography py3-pip skopeo; \
    pip3 install -e /opt; \
    addgroup -g 1000 hs; \
    adduser -D -u 1000 -G hs hs; \
    mkdir -p /srv/tls; \
    chown hs:hs /srv/tls

USER 1000:1000
CMD ["/opt/run.sh"]
