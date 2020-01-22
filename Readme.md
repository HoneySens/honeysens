![HoneySens](logo.png?raw=true "HoneySens Logo")
# HoneySens
HoneySens is a honeypot management platform that supports the deployment of various open source honeypots on a variety of hardware and software architectures. This repository contains the commercial distribution as developed by [T-Systems Multimedia Solutions](https://honeysens.de). It's based on the publicly available repository [Community Edition](https://github.com/sylencecc/honeysens-ce), upon which it will rebased from time to time. Selected features from the commercial version are also backported to the Community Edition between releases.

## Architecture
Each HoneySens installation features a containerized server instance that is used to manage and evaluate events from attached honeypots. The honeypots themselves run within containers on so-called *sensors*, which can be physical or virtual systems running a management process that talks to the server and controls the locally running honeypot containers (*services*). Sensor management as well as the evaluation of event data collected from honeypot services is performed through a web interface offered by the server. HTTPS is the exclusive connection channel for the communication between sensor and server, thus minimizing the dependency of 3rd party network infrastructure. HoneySens is designed for the deployment and management of honeypots in small or medium-sized corporate IP landscapes and mainly meant to detect intruders that originate from within the network. A deployment of sensors with public-facing IP addresses is possible, but might just overwhelm the user with thousands of unfiltered (and mostly uncritical) events per hour. Use at your own risk.

![architecture](architecture.png?raw=true "HoneySens architecture")

## Features
Supported Sensor platforms:
* [BeagleBone Black](https://beagleboard.org/black)
* [Docker (x86)](https://www.docker.com/products/docker-engine)

In terms of honeypot software, we adapt popular open source honeypots so that they are compatible with the event submission API offered by our sensor management daemon (which is in turn part of each sensor firmware). Honeypots are then deployed as *services* to sensors, the concrete way of distribution being the responsibility of the administrator.

We currently ship with modules for the following honeypots:
* [conpot](https://github.com/mushorg/conpot)
* [cowrie](https://github.com/cowrie/cowrie)
* [dionea](https://github.com/DinoTools/dionaea)
* [glastopf](https://github.com/mushorg/glastopf)
* [rdpy](https://github.com/citronneur/rdpy)

In addition to that, HoneySens offers the *recon* service, which is essentially a catch-all daemon that responds to all TCP/UDP requests received by a sensor that are not handled already by any other running honeypot service.

## Installation
Most HoneySens components are built and deployed within Docker containers, which is why building the software doesn't require many external dependencies. The build process with its different parameters is controlled by a set of Makefiles, one per component. 

Detailed build and deployment instructions for all components can be found in their respective subdirectories:
* [Server](server/Readme.md)
* [BBB Sensor Platform](sensor/platforms/bbb/Readme.md)
* [Dockerized Sensor Platform](sensor/platforms/docker_x86/Readme.md)
* [Services](sensor/services/Readme.md)

## Contributors
HoneySens initially started out as a diploma thesis and emerged later into a joint project between the [Technische Universität Dresden](https://tu-dresden.de/), the [Ministry of Interior](http://www.smi.sachsen.de/) of Saxony (Germany) and [T-Systems Multimedia Solutions](https://www.t-systems-mms.com/).

## License
HoneySens Community Edition is licensed under [Apache 2.0 License](https://www.apache.org/licenses/LICENSE-2.0).
