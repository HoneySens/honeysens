FROM ubuntu:20.04
MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

ARG BID
# Defaults
ENV BUILD_ID=$BID \
    CRON_ENABLED=false \
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
    apt-get install -y curl bzip2 mariadb-client netcat; \
    apt-get clean; \
    curl -fsSLO "https://github.com/aptible/supercronic/releases/download/v0.2.1/supercronic-linux-amd64"; \
    echo "d7f4c0886eb85249ad05ed592902fa6865bb9d70  supercronic-linux-amd64" | sha1sum -c -;  \
    chmod +x supercronic-linux-amd64; \
    mv supercronic-linux-amd64 /usr/local/bin/supercronic-linux-amd64; \
    ln -s /usr/local/bin/supercronic-linux-amd64 /usr/local/bin/supercronic; \
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
