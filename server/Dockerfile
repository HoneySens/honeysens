FROM phusion/baseimage:0.9.22

MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

ADD out/web /opt/HoneySens/
RUN /bin/bash /opt/HoneySens/utils/docker/setup_prepare.sh
RUN /bin/bash /opt/HoneySens/utils/docker/setup_release.sh

HEALTHCHECK CMD /usr/bin/test $(/usr/bin/curl -sk https://localhost/api/system/identify) = 'HoneySens'

VOLUME /opt/HoneySens/data
VOLUME /var/lib/mysql

EXPOSE 80 443
CMD ["/sbin/my_init"]