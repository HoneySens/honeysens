# Dockerized Sensor

Dockerized sensors are delivered as a docker image that contains the sensor management daemon along other sensor-specific software. Sensor services will be run within nested docker containers. For that to work, a recent version of docker is required on the host. As an example, the outdated docker vesion 1.6.2 doesn't mount cgroups into containers and therefore won't work. It is known to work with recent versions of Docker CE, specifically version 1.11 and newer. Both build and deployment of a dockerized sensor rely on Docker Compose. Furthermore, this container has to be run in privileged mode for nested containers to work. 

## Build
Analogous to the server, the dockerized sensor can be built in either development or production mode. A recent installation of [Docker Engine](http://www.docker.com/products/docker-engine), GNU make and curl on top of any Linux installations are the only requirements. A recent version of Docker Compose will be automatically fetched during the build process and written to the build directory (`out/`).

To initiate the build process, launch make from within the `sensor/platforms/docker_x86/` directory with one of the following options:
* `make dev`: Builds and launches a development sensor container that continuously watches the local codebase for changes and automatically deploys those to the running instance. **Important**: Before running this command, ensure that `docker-compose-dev.yml` was adjusted to the local environment and the directory  `sensor/platforms/docker_x86/conf/` was created and a valid sensor configuration copied into it (see chapter 'Deployment' below). Use `Strg+C` from the terminal to stop a running dev sensor.
* `make dist` will build and save a production-ready sensor image to `sensor/out/dist/`.
* `make clean` can be used to remove build artifacts (including the dev sensor image) and clean the build directory.

## Deployment
Dockerized sensors are distributed as a `.tar.gz` archive (as result of the aforementioned build process) that contains a couple of files and a single folder:
* `conf/`: Houses the sensor configuration file for this sensor as downloaded from the server
* `firmware.img`: The sensor docker image itself
* `docker-compose.yml`: Compose file, has to be adjusted for the local environment (see below)
* `.env`: Compose environment file, required for unattended firmware updates and should remain in the same directory as the compose file
* `metadata.xml`: Contains further details about the sensor firmware archive and is processed by the server as soon as the firmware archive is uploaded
* `README.md`: The document you're currently reading

To deploy a dockerized sensor, follow these steps:
* Decide on a **networking mode**: There are two ways to connect the sensor container to the network. By default, it will utilize [host networking](https://docs.docker.com/network/host/), which essentially means that the container will share the host's network stack. In this mode, the sensor container will set up a separate docker network for service containers and modify the local firewall (netfilter) rules to ensure that all relevant traffic arrives at the proper destination. However, this mode might interfere with processes on the host system that utilize the network stack as well. This can cause false-positive honeypot events (due to kernel connection tracking timeouts), but might also lead to more severe problems that might render local processes inoperable. In case of problems, it's advisable to use bridge networking. That's the default for any docker container and just means that all the sensor container stuff runs within its own networking stack. However, it comes with the drawback that one has to manually set up firewall rules that redirect traffic to the container. An example for such a netfilter rule that redirects all incoming traffic that doesn't belong to any already active connection to the sensor could be `iptables -t nat -A PREROUTING -i <in_interface> -j DNAT --to-destination <sensor_container_ip>`.  Moreover, a sensor running in this mode can't properly report its external interface address to the server, which will result in an external address (such as `172.17.0.2`) to be shown as the IP address of the sensor on the web interface.
* Unpack the archive, `cd` into the new sensor directory and adjust `docker-compose.yml` to your needs: The default networking mode is *host networking*. In case *bridged networking* should be used, set the option `network_mode` to `bridge`. The environment variable `IFACE` denotes the name of the "honeypot" network interface that the sensor listens on for incoming traffic. If host networking is to be used, set it to the name of the external network interface that should receive the honeypot traffic. Otherwise - in case of bridged networking - `eth0` is the correct setting. The variable `LOG_LVL` specifies the granularity of logging output received from the sensor manager and can be set to either `debug`, `info` or `warn`. You may also adjust the restart policy by adjusting the `restart` setting. In the `volumes` section, also make sure that the local host's docker socket is correctly mounted into the container. This is required for unattended container updates. The default `/var/run/docker.sock` should work for most distributions.
* Load the firmware docker image: `docker load -i firmware.img`
* Copy a proper sensor configuration archive as offered by the HoneySens server into the `conf/` directory. That directory will be mounted into the sensor container on startup. Make sure that the directory doesn't contain any other files or directories except the configuration archive.
* Start the sensor: `HOST_PWD=$(pwd) docker-compose up -d`

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
