# Execute docker build for this Dockerfile from the sensor root directory
FROM alpine:3.21

MAINTAINER Pascal Brueckner pascal.brueckner@sylence.cc

ARG REVISION=unversioned

RUN apk --no-cache add bash
COPY platforms/docker_x86/setup_prepare.sh platforms/docker_x86/setup_release.sh platforms/docker_x86/ifup.sh /srv/
COPY manager /srv/manager
COPY platforms/docker_x86/daemon.json /etc/docker/
COPY platforms/docker_x86/dhcpcd.conf /etc/
COPY platforms/docker_x86/services/ /etc/services.d/
COPY platforms/docker_x86/shutdown/00_stop_docker.sh platforms/docker_x86/shutdown/01_cleanup_network.sh /etc/cont-finish.d/

RUN /srv/setup_prepare.sh $REVISION
RUN /srv/setup_release.sh

ENTRYPOINT ["/init"]
