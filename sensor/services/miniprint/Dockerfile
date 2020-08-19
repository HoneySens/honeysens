ARG ARCH=amd64
FROM multiarch/alpine:${ARCH}-v3.12

COPY honeysens.py prepare.sh server.patch /root/
WORKDIR /app
RUN /bin/sh /root/prepare.sh
CMD ["python3", "/app/server.py", "-b", "0.0.0.0"]
