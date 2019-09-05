FROM phusion/baseimage:0.11

MAINTAINER Pascal Brueckner <pascal.brueckner@sylence.cc>

RUN apt-get -qq update && apt-get -y install hostapd iproute2
COPY apt.list /etc/apt/sources.list.d/
RUN apt-get -qq update && apt-get -y install tinyproxy
COPY eap.users hostapd.conf hostapd_handler.sh tinyproxy.conf /srv/
COPY my_init.d/* /etc/my_init.d/
COPY services/hostapd /etc/service/hostapd/
COPY services/hostapd_listener /etc/service/hostapd_listener/
COPY services/tinyproxy /etc/service/tinyproxy/

CMD ["/sbin/my_init"]