#!/usr/bin/env bash
# Explicitly stops the docker service. This should be happening on shutdown anyway, but for some reason
# the s6 init system kills docker too early, which causes the containers to malfunction when attempting to start
# them after a container restart. This workaround seems to fix that.
s6-svc -wd -d /var/run/s6/services/docker/