FROM alpine:3.11

# Defaults
ENV HS_BROKER_HOST=broker \
    HS_BROKER_PORT=6379 \
    HS_DB_HOST=database \
    HS_DB_PORT=3306 \
    HS_DB_USER=honeysens \
    HS_DB_NAME=honeysens \
    HS_DB_PASSWORD=secret \
    HS_REGISTRY_HOST=registry \
    HS_REGISTRY_PORT=5000

COPY processor /opt/processor/
COPY setup.py /opt/

RUN set -ex; \
        mkdir -p /srv/tls; \
        ln -s /srv/data/https.chain.crt /srv/tls/https.crt; \
        ln -s /srv/data/https.key /srv/tls/https.key; \
        apk --no-cache add py3-cryptography py3-pip skopeo; \
        pip3 install -e /opt; \
        addgroup -g 1000 hs; \
        adduser -D -u 1000 -G hs hs

USER 1000:1000
CMD ["celery", "-A", "processor.processor", "worker", "-B", "-s", "/srv/data/tasks/celerybeat-schedule", "-l", "info", "-Q", "high,low", "-Ofair", "--prefetch-multiplier=1", "--hsconfig=/srv/data/config.cfg"]