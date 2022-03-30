FROM ubuntu:20.04
MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

# Defaults
ENV ACCESS_LOG=false \
    HS_BROKER_HOST=broker \
    HS_BROKER_PORT=6379 \
    HS_DB_HOST=database \
    HS_DB_PORT=3306 \
    HS_DB_USER=honeysens \
    HS_DB_NAME=honeysens \
    HS_DB_PASSWORD=secret \
    DOMAIN=server \
    TLS_FORCE_12=false \
    HS_REGISTRY_HOST=registry \
    HS_REGISTRY_PORT=5000

COPY out/web /opt/HoneySens/
RUN /bin/bash /opt/HoneySens/utils/docker/setup_prepare.sh
RUN /bin/bash /opt/HoneySens/utils/docker/setup_release.sh

HEALTHCHECK CMD /usr/bin/test $(/usr/bin/curl -sk https://localhost/api/system/identify) = 'HoneySens'

VOLUME /opt/HoneySens/data

USER 1000:1000
EXPOSE 80 443
CMD ["/opt/run.sh"]
