# Dockerized sensor

This Dockerfile builds a docker image that contains 
the sensor management daemon. Sensor services will be run
as nested docker containers. For that to work, a recent
version of docker is required. So far docker 1.6.2 doesn't 
mount cgroups into containers and therefore won't work. It
is known to work with docker 1.11 and newer. Furthermore, 
this container has to be run in privileged mode (`--privileged`)
for container nesting to work.

## Build instructions

To build this Dockerfile, the sensor root directory of the
HoneySens source tree has to be used as context, e.g.

`docker build -t honeysens/sensorx86 -f sensor/platforms/docker_x86/Dockerfile sensor/`

## Usage
`docker run -v <path_containing_config>:/etc/manager -e CONFIG_FILE="<config_file_name>" --net=<network> --privileged --rm honeysens/sensorx86`

To connect the sensor to the outside world, the external device of
the host running this container should be bridged to the docker network
interface. It is possible to utilize a separate user-defined network [1]
and to add the external interface, e.g. `eth0` to that bridge. Another
not so ideal option would be to add `eth0` to the default docker bridge.
In both cases the docker container will have raw access to the external
network device while also preserving connectivity for the physical host.

To make honeyd work inside the container, checksum offloading should be
disabled for the virtual bridge connecting the container to the outside
world [2]. Otherwise checksum headers might not be calculated for some
incoming packets, which will subsequently be dropped by honeyd upon
performing checksum verification.

## Next steps

* Service management (probably switch to a Debian base image)
* Update procedure

[1] https://docs.docker.com/engine/userguide/networking/#user-defined-networks
[2] https://wiki.wireshark.org/CaptureSetup/Offloading
