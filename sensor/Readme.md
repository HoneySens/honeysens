# HoneySens Sensor
Sources for building platform-dependent sensors. The general idea here is that
for each platform there exists a process how firmware has to be built, e.g.
BeagleBones use images to be written on SD cards as regular containers. During each of 
those build processes the manager daemon is installed, which later on performs 
its own platform-specific routines, if necessary.

Honeypot software itself is run within "service" containers, which is why each platform
has to provide the means to run docker containers.

## Build instructions
* Platforms: Look up the Readme files within each platform directory for specific instructions. There is currently no
  "build everything" method, because the build process depends on the host architecture (e.g. the BBB ARM firmware can't
  be built on x86 hosts, not even with QEMU)
* Services: These should follow a generic structure and can be built using the provided Makefiles

## Directory structure
* `manager/`: Manager daemon sources
* `platforms/`: Platform-specific code and build scripts (firmware)
* `services/`: Containerized honeypot services
