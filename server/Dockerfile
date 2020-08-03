FROM phusion/baseimage:0.11
MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

# Defaults
ENV ACCESS_LOG=false \
    BROKER_HOST=broker \
    BROKER_PORT=6379 \
    DB_HOST=database \
    DB_PORT=3306 \
    DB_USER=honeysens \
    DB_NAME=honeysens \
    DB_PASSWORD=secret \
    DOMAIN=server \
    TLS_FORCE_12=false \
    REGISTRY_HOST=registry \
    REGISTRY_PORT=5000

COPY out/web /opt/HoneySens/
RUN /bin/bash /opt/HoneySens/utils/docker/setup_prepare.sh
RUN /bin/bash /opt/HoneySens/utils/docker/setup_release.sh

HEALTHCHECK CMD /usr/bin/test $(/usr/bin/curl -sk https://localhost/api/system/identify) = 'HoneySens'

VOLUME /opt/HoneySens/data
VOLUME /var/lib/mysql

EXPOSE 80 443
CMD ["/sbin/my_init"]