FROM debian:bullseye
MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

# Defaults
ENV CRON_ENABLED=false \
    CRON_CONDITION="0 3 * * *" \
    CRON_DBONLY=false \
    CRON_KEEP=0 \
    CRON_TEMPLATE="backup-%s" \
    HS_DB_HOST=database \
    HS_DB_PORT=3306 \
    HS_DB_USER=honeysens \
    HS_DB_NAME=honeysens \
    HS_DB_PASSWORD=secret \
    HS_REGISTRY_HOST=registry \
    HS_REGISTRY_PORT=5000

RUN set -ex; \
    export DEBIAN_FRONTEND=noninteractive; \
    apt-get -qq update; \
    apt-get upgrade -y; \
    apt-get install -y cron bzip2 mariadb-client netcat; \
    apt-get clean; \
    groupadd -g 1000 hs; \
    useradd -m -u 1000 -g 1000 hs; \
    mkdir -p /srv/backup /srv/data /srv/registry; \
    chown -R hs:hs /srv

COPY run.sh /opt/
COPY backup.sh /usr/local/bin/backup
COPY restore.sh /usr/local/bin/restore
COPY reset.sh /usr/local/bin/reset

USER 1000:1000
CMD ["/opt/run.sh"]
