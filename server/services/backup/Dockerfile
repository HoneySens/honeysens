FROM debian:buster

MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

RUN apt-get -qq update && apt-get install -y mariadb-client netcat bzip2 && apt-get clean

ADD run.sh /opt/
ADD backup.sh /usr/local/bin/backup
ADD restore.sh /usr/local/bin/restore
ADD reset.sh /usr/local/bin/reset

CMD ["/opt/run.sh"]