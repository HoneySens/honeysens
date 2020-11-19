ARG ARCH=amd64
FROM multiarch/alpine:${ARCH}-v3.12

COPY prepare.sh honeysens_logger.py heralding.patch honeypot.patch /root/
RUN /bin/sh /root/prepare.sh

ENV PATH=/root/.local/bin:$PATH

CMD ["heralding" ]
EXPOSE 21 23 25 80 110 143 443 465 993 995 1080 3306 5432 5900
