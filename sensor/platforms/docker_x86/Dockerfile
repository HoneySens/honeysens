# Execute docker build for this Dockerfile from the sensor root directory
FROM smebberson/alpine-base:3.2.0
MAINTAINER Pascal Brueckner pascal.brueckner@tu-dresden.de

ARG REVISION=unversioned

# Basic requirements
RUN apk --no-cache add py-pip py-curl ca-certificates alpine-sdk python-dev linux-headers docker dhcpcd py-cryptography

# Install honeyd dependencies
RUN apk --no-cache add libevent-dev libdnet-dev libpcap-dev pcre-dev libedit-dev automake autoconf zlib-dev libtool

# Install docker, docker-py and macchanger from alpine 3.6
RUN apk add 'docker>17.05.0' --no-cache --repository http://nl.alpinelinux.org/alpine/v3.6/community
RUN apk add 'docker-py>2.3.0' --no-cache --repository http://nl.alpinelinux.org/alpine/v3.6/community
RUN apk add macchanger --no-cache --repository http://nl.alpinelinux.org/alpine/v3.6/community
RUN apk add cntlm --no-cache --repository http://nl.alpinelinux.org/alpine/v3.9/community

COPY manager /opt/manager
COPY platforms/docker_x86/prepare.sh platforms/docker_x86/install_arpd.sh platforms/docker_x86/honeyd.diff platforms/docker_x86/arpd.diff platforms/docker_x86/ifup.sh /root/
COPY platforms/docker_x86/services/ platforms/docker_x86/services/ /etc/services.d/
COPY platforms/docker_x86/dhcpcd.conf /etc/

# Builder scripts
RUN /root/prepare.sh $REVISION
RUN /root/install_arpd.sh
