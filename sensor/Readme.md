# HoneySens Sensor
Sources for building sensor firmware, which is highly platform-dependent. For example, BeagleBones require disk images that are written to SD cards while dockerized sensors are provided as container images. These firmware images consist of a small Linux base system, a management daemon called [manager](manager/Readme.md) that synchronizes state between sensor and server and the actual honeypot software, which is running within "service" containers on top of Docker. Therefore, each platform has to provide the means to run docker containers. In addition, the repository contains some code for platform-specific features, such as a proprietary LED extension board for the BeagleBones to visually show the current sensor state.

## Build instructions
**Platforms**: Look up the Readme files within each platform directory for build instructions. There is currently no
  *build everything* switch, because the build process depends on the host architecture (e.g. the BBB ARM firmware can't be built on x86 hosts, not even with QEMU).
* [BBB Sensor Platform](platforms/bbb/Readme.md)
* [Dockerized Sensor Platform](platforms/docker_x86/Readme.md)

**Services** are all structured similarly and can be built using the provided Makefiles.

## Directory structure
* `manager/`: Manager daemon sources
* `platforms/`: Platform-specific code and build scripts (firmware)
* `services/`: Containerized honeypot services
* `utils/`: Utilities for deployment, testing and demonstration
