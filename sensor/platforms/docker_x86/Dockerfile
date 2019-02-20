# Execute docker build for this Dockerfile from the sensor root directory
FROM woahbase/alpine-s6

MAINTAINER Pascal Brueckner pascal.brueckner@sylence.cc

ARG REVISION=unversioned

# Basic requirements
RUN apk --no-cache add py-pip py-curl ca-certificates alpine-sdk python-dev linux-headers docker dhcpcd py-cryptography docker docker-py macchanger cntlm

# Install honeyd dependencies
RUN apk --no-cache add libevent-dev libdnet-dev libpcap-dev pcre-dev libedit-dev automake autoconf zlib-dev libtool

COPY manager /opt/manager
COPY platforms/docker_x86/prepare.sh platforms/docker_x86/install_arpd.sh platforms/docker_x86/honeyd.diff platforms/docker_x86/arpd.diff platforms/docker_x86/ifup.sh /root/
COPY platforms/docker_x86/services/ platforms/docker_x86/services/ /etc/services.d/
COPY platforms/docker_x86/dhcpcd.conf /etc/

# Builder scripts
RUN /root/prepare.sh $REVISION
RUN /root/install_arpd.sh

ENTRYPOINT ["/init"]
