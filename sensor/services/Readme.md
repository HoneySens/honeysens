# Services
In the HoneySens architecture, *services* denominate low-interaction honeypots that are deployed as Docker containers to sensors. Depending on the hardware platform of each sensor, these dockerized honeypots can be built for multiple architectures (currently `amd64` and `armhf`). Since the build process is done entirely within containers, a recent installation of the [Docker Engine](https://www.docker.com/products/docker-engine) and GNU make are sufficient to build each service, even cross-platform.

To build a service, simply `cd` to the service directory, such as `sensor/services/recon/`, and issue `make amd64`, `make armhf` or `make all`. Please keep in mind that HoneySens currently only supports the ARM-based BeagleBone Black platform, which is why only the `armhf` version might be of use. After a successful build, the resulting service tarball(s) can be found in `sensor/services/<service>/out/dist/`. They contain the docker image together with some additional metadata about the honeypot service itself and are ready to be uploaded to the HoneySens web interface. The resulting docker images will also be registered on the build host within the `honeysens/<service>` namespace. The target `make clean` can be utilized to clean the `out/` directory.

## Service matrix

| Name | Protocol/Purpose | URL | Revision | Ports (TCP) | Ports (UDP) |
| ---- | ------- | --- | -------- | ----------- | ----------- |
| Conpot | ICS/SCADA honeypot | https://github.com/mushorg/conpot | 0.5.2 | 80, 102, 502, 47808 | |
| Cowrie | SSH server with pseudo interactive shell | https://github.com/cowrie/cowrie | 1.6.0 | 22 | |
| dionaea | SMB server that recognizes various CVEs | https://github.com/DinoTools/dionaea | 1426750b9fd09c5bfeae74d506237333cd8505e2 | 445 | |
| Glastopf | Simple HTTP server that lures attackers with randomly generated sites full of exploitable keywords | https://github.com/mushorg/glastopf | f9ac53e685991ffe2402f9cb3eb5ccdc2d0b198c | 80 | |
| Heralding | Multi-protocol credentials catching honeypot server | https://github.com/johnnykv/heralding | 1.0.7 | 21, 23, 25, 110, 143, 443, 465, 993, 995, 1080, 3306, 5432, 5900, 8080 | |
| miniprint | Honeypot that acts like a network printer | https://github.com/sa7mon/miniprint | b5c8aa4f990869d22a00a054f1a08edf12502a1f | 9100 | |
| RDPY | RDP server | https://github.com/citronneur/rdpy | cef16a9f64d836a3221a344ca7d571644280d829 | 3389 | |
| recon | Catch-all service, performs TCP 3-way-handshake and logs received packets | (internal) | - | * | * |


