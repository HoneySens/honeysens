FROM debian:buster

MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

RUN apt-get -qq update && apt-get install -y mariadb-client netcat bzip2 && apt-get clean

COPY run.sh /opt/
COPY backup.sh /usr/local/bin/backup
COPY restore.sh /usr/local/bin/restore
COPY reset.sh /usr/local/bin/reset

CMD ["/opt/run.sh"]