![HoneySens](logo.png?raw=true "HoneySens Logo")
# HoneySens
HoneySens is a honeypot management platform that supports the deployment of various open source honeypots on different hardware and software platforms.

## Architecture
Each HoneySens installation features a containerized server instance that is used to manage and evaluate events from attached honeypots. The honeypots themselves run within containers on so-called *sensors*, which can be physical or virtual systems running a management process that talks to the server and controls the locally running honeypot containers (*services*). Sensor management as well as the evaluation of event data collected from honeypot services is performed through a web interface offered by the server. HTTPS is the exclusive connection channel for the communication between sensor and server, thus minimizing the dependency of 3rd party network infrastructure. HoneySens is designed for the deployment and management of honeypots in small or medium-sized corporate IP landscapes and mainly meant to detect intruders that originate from within the network. A deployment of sensors with public-facing IP addresses is possible, but might just overwhelm the user with thousands of unfiltered (and mostly uncritical) events per hour. Use at your own risk.

![architecture](architecture.png?raw=true "HoneySens architecture")

## Features
Supported Sensor platforms:
* [BeagleBone Black](https://beagleboard.org/black)
* [Docker (x86)](https://www.docker.com/products/docker-engine)

In terms of honeypot software, we adapt popular open source honeypots so that they are compatible with the event submission API offered by our sensor management daemon (which is in turn part of each sensor firmware). Honeypots are then deployed as *services* to sensors, the exact distribution being the responsibility of the administrator.

We currently ship with modules for the following honeypots:
* [conpot](https://github.com/mushorg/conpot)
* [cowrie](https://github.com/cowrie/cowrie)
* [dionea](https://github.com/DinoTools/dionaea)
* [glastopf](https://github.com/mushorg/glastopf)
* [rdpy](https://github.com/citronneur/rdpy)

In addition to that, HoneySens offers the *recon* service, which is essentially a catch-all daemon that responds to all TCP/UDP requests received by a sensor that are not handled already by any other running honeypot service.

## Build
Most HoneySens components are built and run within Docker containers, which is why building the software doesn't require many external dependencies. The build process with its different parameters is controlled by a set of Makefiles, one per component. The following section will go into detail how each component can be built.

### Server
The server can be built either in development or production mode. For a deployment in either mode, a recent installation of the [Docker Engine](https://www.docker.com/products/docker-engine), GNU make and curl on top of any Linux installation are the only requirements. The build process relies on Docker Compose, which will be fetched automatically and written to the build directory (`out/`).

To initiate the build process, launch make from within the `server/` directory in one of the following ways:
* `make dev`: Builds and launches a development server system that continuously watches the local codebase for changes and automatically deploys those to the running dev instance. By default, the ports 80 (HTTP) and 443 (HTTPS) are published to the host system for easier access. Modify `docker-compose-dev.yml` if you want to change that behaviour. Use `Strg+C` from the terminal to stop a running dev server.
* `make dist` (default) will build and save a production-ready server image to `server/out/dist/`. For that, it will internally first create and launch a development image to assemble the codebase. Afterwards, the build process for the actual production server image will be launched.
* `make reset` will shut down the development server and remove associated volumes, thus resetting that instance.
* `make clean` can be used to shut down the development server, remove associated volumes and all build artifacts (including the development docker image). However, this command won't clean the entire codebase, so that PHP dependencies don't have to be re-downloaded for each new build process.

After the build process is complete, the resulting Docker images `honeysens/server-dev` and `honeysens/server` are available on your system.

### Sensor
The build process for the sensor firmware depends heavily on the sensor platform. 

#### BeagleBone Black
Here, the build process relies on the [OMAP image builder](https://github.com/RobertCNelson/omap-image-builder) project. Since the image builder only supports building on ARM devices ([source](https://github.com/RobertCNelson/omap-image-builder/issues/118)), we recommended to do so as well. Apart from GNU make and git, the build process only relies on tools that are typically part of a default Linux installation. Consult the Makefile for details.

To initiate the BeagleBone Black firmware build process, `cd` to the directory `sensor/platforms/bbb/` and execute `make`. If the build was successful, the resulting firmware tarball can be found in `sensor/platforms/bbb/out/dist/`. The target `make clean` can be utilized to clean the `out/` directory.

#### Docker (x86)
To build a dockerized sensors image, `cd` into the directory `sensor/patforms/docker_x86` and issue `make`. This will generate and register a docker image as `honeysens/sensorx86:<revision>`. Additionally, a firmware archive ready to be uploaded to the HoneySens web interface will be written to `sensor/platforms/docker_x86/out/dist/`. The target `make clean` can also be utilized to clean the build directory.

A sensor update and deployment script 1s supplied under `sensor/platforms/docker_x86/launcher/launch.py`.

### Services
In the HoneySens architecture, *services* denominate low-interaction honeypots that are deployed as Docker containers to sensors. Depending on the hardware platform of each sensor, these dockerized honeypots can be built for multiple architectures (currently `amd64` and `armhf`). Since the build process is done entirely within containers, a recent installation of the [Docker Engine](https://www.docker.com/products/docker-engine) and GNU make are sufficient to build each service, even cross-platform.

To build a service, simply `cd` to the service directory, such as `sensor/services/recon/`, and issue `make amd64`, `make armhf` or `make all`. Please keep in mind that HoneySens currently only supports the ARM-based BeagleBone Black platform, which is why only the `armhf` version might be of use. After a successful build, the resulting service tarball(s) can be found in `sensor/services/<service>/out/dist/`. They contain the docker image together with some additional metadata about the honeypot service itself and are ready to be uploaded to the HoneySens web interface. The resulting docker images will also be registered on the build host within the `honeysens/<service>` namespace. The target `make clean` can be utilized to clean the `out/` directory.

## Deployment
First ensure that the server image has been registered on the target host (either after a build done on the same host or with `docker load`).  For the actual deployment, usage of [Docker Compose](https://docs.docker.com/compose/) is recommended. The `server/` directory contains a file `docker-compose.yml` that can be used as a blueprint for a deployment. Please consult that file and adjust as necessary, especially the `DOMAIN` environment variable which should contain the expected domain name of the server as well as the `DB_*` variables and database container configuration. Afterwards, simply change to that directory and issue `docker-compose up` to initiate the deployment process. This will start two containers, one for the API and web interface, the other one hosts the internal service registry.

After the server has been started, access its web interface through a web browser to perform the initial system setup. Further steps include the upload of previously built firmware and service images, as well as the registration and deployment of sensors. Documentation is distributed along with the server and can be accessed through the web interface via the *Info* module. It is currently not up to date, but still sufficient to learn the basics.

## Contributors
HoneySens initially started out as a diploma thesis and emerged later into a joint project between the [Technische Universität Dresden](https://tu-dresden.de/), the [Ministry of Interior](http://www.smi.sachsen.de/) of Saxony (Germany) and [T-Systems Multimedia Solutions](https://www.t-systems-mms.com/).

## License
HoneySens Community Edition is licensed under [Apache 2.0 License](https://www.apache.org/licenses/LICENSE-2.0).
