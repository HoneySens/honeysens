#!/usr/bin/with-contenv sh
# Workaround that explicitly sets the docker service status to 'down'. Otherwise the dockerd doesn't shut down properly
# if there are some containers still running (could be a bug in either dockerd or the s6 init system), which
# causes issues on the next restart.
s6-svc -wd -d /var/run/s6/services/docker/