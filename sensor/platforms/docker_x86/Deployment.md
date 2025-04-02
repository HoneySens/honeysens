# Deploying a dockerized sensor
Dockerized sensors are delivered as a docker image that contains the sensor management daemon along other sensor-specific software. Sensor services will be run within nested docker containers. For that to work, a recent version of docker is required on the host. As an example, the outdated docker vesion 1.6.2 doesn't mount cgroups into containers and therefore won't work. It is known to work with recent versions of Docker CE, specifically version 1.11 and newer. Both build and deployment of a dockerized sensor rely on Docker Compose, which has meanwhile been integrated into the docker CLI and isn't an individual dependency anymore. Furthermore, this container has to be run in privileged mode for nested containers to work. 

## Instructions
Dockerized sensor images are distributed as `.tar.gz` archives (as result of the aforementioned build process) with the following content:
* `conf/`: Houses the sensor configuration file for this sensor as downloaded from the server
* `firmware.img`: The sensor docker image itself
* `docker-compose.yml`: Compose file, has to be adjusted for the local environment (see below)
* `.env`: Compose environment file, required for unattended firmware updates and should remain in the same directory as the compose file
* `metadata.xml`: Contains further details about the sensor firmware archive and is processed by the server as soon as the firmware archive is uploaded
* `Readme.md`: The document you're currently reading

To deploy a dockerized sensor, follow these steps:
* Decide on a networking mode (see below). *Note*: Sensor don't support `nftables` yet, please ensure that `iptables` is enabled (for Debian Buster and later, consult the [Wiki](https://wiki.debian.org/iptables)).
* Unpack the archive, `cd` into the new directory and adjust `docker-compose.yml` to your needs, especially the network configuration. The variable `LOG_LVL` specifies the granularity of logging output received from the sensor manager and can be set to either `debug`, `info` or `warn`. You may also adjust the restart policy by adjusting the `restart` setting. In the `volumes` section, also make sure that the local host's docker socket is correctly mounted into the container. This is required for unattended container updates. The default `/var/run/docker.sock` should work for most distributions.
* Load the firmware docker image: `docker load -i firmware.img`
* Copy a proper sensor configuration archive obtained from the server into the `conf/` directory. That directory will be mounted into the sensor container on startup. Make sure that the directory doesn't contain any other files or directories except the configuration archive.
* Start the sensor: `HOST_PWD=$(pwd) docker compose up -d`

### Networking modes
In general, we support two modes of operation when connecting a sensor container to the outside world: Host and bridged networking. The networking setup is a combination of configuration parameters in the web frontend (which result in a configuration archive) and Docker-specific configuration options during deployment, usually via the respective `docker-compose.yml` file.
* **Host networking**: This mode is the default. Here we utilize the [host networking](https://docs.docker.com/network/host/) support from Docker to share the container's network stack with that of its host. In this mode, the sensor container applies the networking configuration set up in the frontend to the interface given via the `IFACE` environment variable. That parameter should be set to the "honeypot" interface that receives traffic from the outside. In case the interface management is done by other processes on the host, the sensor network can be set to `unconfigured` within the web frontend. Additionally, the sensor container will set up a new docker network for service containers (essentially a Linux bridge) and modify the host's firewall (netfilter) rules to ensure that all relevant traffic arrives at the proper destination containers. However, this mode might interfere with processes on the host system that utilize the network stack as well. This can cause false-positive honeypot events (due to kernel connection tracking timeouts), but might also lead to more severe problems that might render local processes inoperable. In case of problems, it's advisable to fall back to bridged networking.

  To deploy a sensor in host networking mode, make sure that in `docker-compose.yml`
    * `network_mode` is set to `host`
    * the environment variable `IFACE` is set to the name of a local interface that external (honeypot) connections are expected on
    * there is no `networks` section defined

* **Bridged networking**: In this mode the sensor container will spawn with its own network stack. This way, sensor operations are clearly separated from the host's network. However, this comes with the drawback that we have to manually set up firewall rules that redirect traffic to the sensor container. An example for such a netfilter rule that redirects all incoming traffic that doesn't belong to any already active connection to the sensor could be `iptables -t nat -A PREROUTING -i <in_interface> -j DNAT --to-destination <sensor_container_ip>`. Moreover, a sensor running in this mode can't properly report its external interface address to the server, which will result in an external address (such as `172.17.0.2`) to be shown as the IP address of the sensor on the web interface. When a sensor is run in development mode, bridge-based networking is the default.

  To deploy a sensor in bridged networking mode, make sure that in `docker-compose.yml`
    * `network_mode` ist set to `bridge`
    * the environment variable `IFACE` is set to `eth0`