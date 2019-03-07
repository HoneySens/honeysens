# Execute docker build for this Dockerfile from the sensor root directory
FROM woahbase/alpine-s6

MAINTAINER Pascal Brueckner pascal.brueckner@sylence.cc

ARG REVISION=unversioned

COPY platforms/docker_x86/setup_prepare.sh platforms/docker_x86/setup_release.sh platforms/docker_x86/ifup.sh /opt/
COPY manager /opt/manager
COPY platforms/docker_x86/dhcpcd.conf /etc/
COPY platforms/docker_x86/services/ platforms/docker_x86/services/ /etc/services.d/
COPY platforms/docker_x86/shutdown/00_stop_docker.sh platforms/docker_x86/shutdown/01_cleanup_network.sh /etc/cont-finish.d/

RUN /opt/setup_prepare.sh $REVISION
RUN /opt/setup_release.sh

ENTRYPOINT ["/init"]
