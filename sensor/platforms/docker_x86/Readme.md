# Dockerized Sensor
Dockerized sensors are delivered as a docker image that contains the sensor management daemon along other sensor-specific software. Sensor services will be run within nested docker containers. For that to work, a recent version of docker is required on the host. As an example, the outdated docker vesion 1.6.2 doesn't mount cgroups into containers and therefore won't work. It is known to work with recent versions of Docker CE, specifically version 1.11 and newer. Both build and deployment of a dockerized sensor rely on Docker Compose. Furthermore, this container has to be run in privileged mode for nested containers to work. 

## Build
Analogous to the server, the dockerized sensor can be built in either development or production mode. A recent installation of [Docker Engine](http://www.docker.com/products/docker-engine), GNU make and curl on top of any Linux installations are the only requirements. A recent version of Docker Compose will be automatically fetched during the build process and written to the build directory (`out/`).

To initiate the build process, launch make from within the `sensor/platforms/docker_x86/` directory with one of the following options:
* `make dev`: Builds and launches a development sensor container that continuously watches the local codebase for changes and automatically deploys those to the running instance. **Important**: Before running this command, ensure that `docker-compose-dev.yml` was adjusted to the local environment and the directory  `sensor/platforms/docker_x86/conf/` was created and a valid sensor configuration copied into it (see chapter 'Deployment' below). Use `Strg+C` from the terminal to stop a running dev sensor.
* `make dist` will build and save a production-ready firmware tarball to `sensor/platforms/docker_x86/out/dist/`.
* `make reset` will shut down the sensor container and remove associated volumes, thus resetting that instance.
* `make clean` can be used to remove build artifacts (including the dev sensor image) and clean the build directory.

## Deployment
Deploying sensors is covered in a [separate document](Deployment.md) that is also included when building this image for production.

## Misc
### Unattended updates
The dockerized sensor supports unattended firmware updates by mounting the host's dockerd socket into the sensor container. This way the sensor manager can access the host's docker process, register new firmware revisions and create new sensor instances. The automatic updates process works roughly as follows:
* Download and extraction of new firmware archive that was registered on the server
* Registration of the firmware image with the host's docker daemon
* Update of the sensor compose file  - which is mounted into the container - to use the new image
* Update of the compose environment file `.env` with a new compose project name
* Startup of a new container with the environment variable `PREV_PREFIX` set to the current (old) compose project name. This way the new sensor container will properly clean up and remove the current sensor instance on startup.
* Shutdown of the current - now outdated - sensor container

### Honeyd
Honeyd is a honeypot framework that might be integrated into the sensor software in the future. It allows us to simulate the networking stack of various devices and operating systems, thus increasing our credibility to be a real host. To run honeyd inside of a container, [checksum offloading](https://wiki.wireshark.org/CaptureSetup/Offloading) should be disabled for the virtual bridge connecting the container to the outside world [1]. Otherwise checksum headers might not be calculated for some incoming packets, which will subsequently be dropped by honeyd upon performing checksum verification.
