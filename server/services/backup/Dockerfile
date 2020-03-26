FROM debian:buster
MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

# Defaults
ENV CRON_ENABLED=false \
    CRON_CONDITION="0 3 * * *" \
    CRON_KEEP=0 \
    CRON_TEMPLATE="backup-%s" \
    DB_HOST=database \
    DB_PORT=3306 \
    DB_USER=honeysens \
    DB_NAME=honeysens \
    DB_PASSWORD=secret

RUN apt-get -qq update && apt-get install -y cron bzip2 mariadb-client netcat && apt-get clean

COPY run.sh /opt/
COPY backup.sh /usr/local/bin/backup
COPY restore.sh /usr/local/bin/restore
COPY reset.sh /usr/local/bin/reset

CMD ["/opt/run.sh"]