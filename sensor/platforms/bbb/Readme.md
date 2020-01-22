# BeagleBone Black Platform
Software for the BeagleBone Black (BBB) is distributed as an installer image that has to be written (together with a sensor configuration archive provided by the server) to a micro SD card to boot the BBB from. Upon boot the installer will wipe the board's internal eMMC and copy over the new system accompanied by the specific sensor configuration. This process is based on the official images distributed by the vendor. Since our aim is to centralize sensor management, updates will utilize a similar process: A new firmware installer is automatically distributed by the server, written to the external micro SD card and launched with a system restart. The current sensor configuration will survive that process. After a while, the BBB will restart into the new sensor system.

## Build
The build process relies on the official [OMAP image builder](https://github.com/RobertCNelson/omap-image-builder) project. Since the image builder only supports building on ARM devices ([source](https://github.com/RobertCNelson/omap-image-builder/issues/118)), we recommended to do so as well. Apart from GNU make and git, the build process only requires tools that are by default installed on most Linux distributions. Consult the Makefile for details.

To initiate the BeagleBone Black firmware build process, checkout this repository on a BeagleBone Black, `cd` to the directory `sensor/platforms/bbb/` and execute `make`. If the build was successful, the resulting production-ready firmware tarball can be found in `sensor/platforms/bbb/out/dist/`. The target `make clean` can be utilized to clean the `out/` directory.

## Deployment
Firmware images for the BBB platform are distributed as `.tar.gz` archives (as result of the aforementioned build process) with the following content:
* `firmware.img`: The firmware installer image ready to be written to micro SD card
* `metadata.xml`: Contains further details about the sensor firmware archive and is processed by the server as soon as the firmware archive is uploaded

To deploy a BeagleBone-based sensor, follow these steps:
* Unpack the archive, `cd` into the new directory and write the `firmware.img` file to a micro SD card, e.g. with `dd`: `dd if=firmware.img of=/dev/mmcblk0`.
* The resulting micro SD card will have two partitions. Mount the first, smaller partition (it should have a size of roughly 100 MB) and copy a proper sensor configuration archive obtained from the server into its top-level directory. Then unmount that partition.
* Insert the micro SD card into the BeagleBone Black, then attach Ethernet and power cables to boot up the system. The LEDs on the board will indicate that the installation is in progress. After ten to twenty minutes, which mostly depends on the quality of your micro SD card, the system will restart into the new firmware and try to contact the server.
