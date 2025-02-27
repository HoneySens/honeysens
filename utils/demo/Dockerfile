FROM ubuntu:22.04

ARG SNAPSHOT_PATH
ARG SENSOR_CONFIG_PATH
ENV HS_DB_HOST=database \
    HS_DB_PORT=3306 \
    HS_DB_USER=honeysens \
    HS_DB_NAME=honeysens \
    HS_DB_PASSWORD=honeysens

RUN set -ex; \
    export DEBIAN_FRONTEND=noninteractive; \
    apt-get -qq update; \
    apt-get upgrade -y; \
    apt-get install -y bzip2 mariadb-client; \
    apt-get clean; \
    groupadd -g 1000 hs; \
    useradd -m -u 1000 -g 1000 hs; \
    mkdir -p /srv/backup /srv/data /srv/registry /srv/sensor; \
    chown -R hs:hs /srv

COPY restore.sh /usr/local/bin/restore
COPY ${SNAPSHOT_PATH} /srv/backup/snapshot.tar.bz2
COPY ${SENSOR_CONFIG_PATH} /srv/backup/sensor.tar.gz

CMD ["/usr/local/bin/restore", "/srv/backup/snapshot.tar.bz2", "/srv/backup/sensor.tar.gz"]