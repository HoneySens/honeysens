# Dockerized Sensor
Dockerized sensors are provided as docker images that contain a minimal base system, the sensor management daemon, a nested docker daemon and some sensor-specific adjustments. Sensor services will be run within nested docker containers. Both build and deployment processes of a dockerized sensor utilize `docker compose`. Furthermore, the sensor container itself has to be run in privileged mode so that nested containers can be created. 

## Build
Analogous to the server, the dockerized sensor can be built in either development or production mode. A recent installation of [Docker Engine](https://docs.docker.com/engine/), GNU make and curl are required. Building has only been tested on Linux hosts.

To initiate the build process, launch make from within the `sensor/platforms/docker_x86/` directory with one of the following options:
* `make dev`: Builds and launches a development sensor container that continuously watches the local codebase for changes and automatically restarts the running instance. **Important**: Before running this command, ensure that `docker-compose-dev.yml` was adjusted to the local environment and the directory  `sensor/platforms/docker_x86/conf/` was created and a valid sensor configuration copied into it (see chapter 'Deployment' below). Use `Strg+C` from the terminal to stop a running dev sensor.
* `make dist` will build and save a production-ready firmware tarball to `sensor/platforms/docker_x86/build/dist/`.
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