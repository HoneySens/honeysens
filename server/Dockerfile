FROM ubuntu:22.04
MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

ARG BID
# Defaults
ENV ACCESS_LOG=false \
    BUILD_ID=$BID \
    DOMAIN=server \
    HS_BROKER_HOST=broker \
    HS_BROKER_PORT=6379 \
    HS_DB_HOST=database \
    HS_DB_PORT=3306 \
    HS_DB_USER=honeysens \
    HS_DB_NAME=honeysens \
    HS_DB_PASSWORD=secret \
    HS_DB_ROOT_PASSWORD=secret \
    HS_REGISTRY_HOST=registry \
    HS_REGISTRY_PORT=5000 \
    PLAIN_HTTP_API=false \
    TLS_FORCE_12=false

COPY out/web /opt/HoneySens/
RUN /bin/bash /opt/HoneySens/utils/docker/setup_prepare.sh
RUN /bin/bash /opt/HoneySens/utils/docker/setup_release.sh

HEALTHCHECK CMD /usr/bin/test $(/usr/bin/curl -sk https://localhost:8443/api/system/identify) = 'HoneySens'

VOLUME /opt/HoneySens/data

USER 1000:1000
EXPOSE 8080 8443
CMD ["/opt/run.sh"]
