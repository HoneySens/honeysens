![HoneySens](logo.png?raw=true "HoneySens Logo")
# HoneySens
HoneySens is a honeypot management platform that supports the deployment of various open source honeypots on different hardware and software platforms.

## Architecture
Each HoneySens installation features a containerized server that is used to monitor events from attached honeypots. The honeypots themselves run within containers on so-called *sensors*, which can be physical or virtual systems running a management process that talks to the server and controls the locally running honeypot containers (*services*). Sensor management as well as the evaluation of event data collected from honeypot services is performed through a web interface running on the server. HTTPS is the exclusive connection channel for the communication between sensor and server. HoneySens is designed for the deployment and management of honeypots in small or medium-sized corporate IP landscapes and intended to serve as early-detection system for intruders that originate from within the own network. A deployment of sensors with public-facing IP addresses is possible, but might just overwhelm the user with thousands of unfiltered (and mostly uncritical) events per hour. Use at your own risk.

![architecture](architecture.png?raw=true "HoneySens architecture")

## Features
Supported Sensor platforms:
* [BeagleBone Black](https://www.beagleboard.org/boards/beaglebone-black)
* [Docker (x86)](https://docs.docker.com/engine/)

In terms of honeypot services, we adapt popular open source honeypots so that they are compatible with the event submission API offered by our sensor management daemon (which is in turn part of the sensor firmware).

We currently ship with modules for the following honeypots:
* [conpot](https://github.com/mushorg/conpot)
* [cowrie](https://github.com/cowrie/cowrie)
* [dionea](https://github.com/DinoTools/dionaea)
* [glastopf](https://github.com/mushorg/glastopf)
* [heralding](https://github.com/johnnykv/heralding)
* [miniprint](https://github.com/sa7mon/miniprint)
* [rdpy](https://github.com/citronneur/rdpy)

In addition to that, HoneySens offers the in-house *recon* service, which is essentially a catch-all daemon that responds to all TCP/UDP requests received by a sensor that are not handled already by any other running honeypot service.

## Build
Most HoneySens components are built and deployed within Docker containers, which is why building the software doesn't require many external dependencies. The build process is controlled by a set of Makefiles, one per component. 

Detailed build and deployment instructions for all components can be found in their respective subdirectories:
* [Server](server/Readme.md)
* [Sensors](sensor/Readme.md)
* [Services](sensor/services/Readme.md)

## Documentation
For an conceptional overview, deployment and maintenance documentation, please visit [honeysens.org](https://honeysens.org/docs).

## Contributors
HoneySens initially started out as a diploma thesis and emerged later into a joint project between the [Technische Universität Dresden](https://tu-dresden.de/), the [Ministry of Interior](http://www.smi.sachsen.de/) of Saxony (Germany) and [T-Systems Multimedia Solutions](https://www.t-systems-mms.com/). A commercial license with support can be obtained from [T-Systems MMS](https://honeysens.de).

## License
HoneySens is licensed under [Apache 2.0 License](https://www.apache.org/licenses/LICENSE-2.0).
