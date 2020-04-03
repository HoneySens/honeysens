FROM alpine:3.11

# Defaults
ENV BROKER_HOST=broker \
    BROKER_PORT=6379 \
    DB_HOST=database \
    DB_PORT=3306 \
    DB_USER=honeysens \
    DB_NAME=honeysens \
    DB_PASSWORD=secret \
    REGISTRY_HOST=registry \
    REGISTRY_PORT=5000

COPY processor /opt/processor/
COPY setup.py /opt/

RUN set -ex; \
        apk --no-cache add py3-pip skopeo; \
        pip3 install -e /opt

CMD ["celery", "-A", "processor.processor", "worker", "-l", "info", "-Q", "high,low", "-Ofair", "--uid", "33", "--prefetch-multiplier=1", "--hsconfig=/srv/data/config.cfg"]