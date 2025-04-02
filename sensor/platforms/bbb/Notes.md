# Notes on the BeagleBone Black image generation process

Note: Legacy documentation, but might still be relevant to understand the build process in more detail.

Images can be built from scratch using the official [GitHub image builder repository](https://github.com/beagleboard/image-builder). The script `RootStock-NG.sh` bootstraps an armhf Debian root filesystem according to some provided config (distribution, release etc). Afterwards an (SD-card compatible) image can be built using the `setup_sdcard.sh` script with the following relevant options:
* `--img <name>`: The name of the generated image file (that will have some suffixes from other options and the `img` extension added automatically)
* `--dtb beaglebone`: Platform to create the image for (always beaglebone for the bbb)
* `--boot_label`: Label of the first partition within the image, which is the FAT partition in our case (two partition layout)
* `--enable-systemd`: Required for bootstrapped distributions using systemd (Debian Jessie and newer)
* `--bbb-old-bootloader-in-emmc`: Required when the internal eMMC of the bbb board contains a bootable system with the 'old' boot layout (as it seems). Using this option will result in a file `uEnv.txt` on the first FAT partition, that contains u-boot configuration directives to boot from the SD card. Without this file the u-boot will abort the SD boot process and try to boot from the eMMC. In the new layout the uEnv.txt file is placed on the second partition within `/boot`.
* `--hostname`: `/etc/hostname` of the resulting system
* `--beagleboard.org-production`: Creates a layout with two partitions, the first one being FAT and the second one ext4
* `--bbb-flasher`: The resulting image starts the flash process after booting, that clones the system to the eMMC

The HoneySens sensor debian package can't be built reliably in a chroot with qemu-arm-static (using pbuilder/pdebuild - see [Building ARM debs with pbuilder](http://jodal.no/2015/03/08/building-arm-debs-with-pbuilder/), because currently QEMU doesn't handle multi-threaded applications correctly (see [this](https://lists.gnu.org/archive/html/qemu-discuss/2014-09/msg00070.html)). We therefore should switch to a traditional [CrossToolchain](https://wiki.debian.org/CrossToolchains).
UPDATE: Seems to work most of the time anyway. Might check the debuild output for 'Segmentation fault' messages)

Usage of --bbb-old-bootloader will in practice rename the otherwise unused file /boot/uboot/bbb-uEnv.txt (on the boot partition) to uEnv.txt.

The usage of the --bbb-flasher option only leads to a single difference in the generated image, namely that in /boot/uEnv.txt the following directive will be commented out:
#cmdline=init=/opt/scripts/tools/eMMC/init-eMMC-flasher-v3.sh

---------------
Example build process on a Debian host:

```
# prepare pbuilder bootstrap image as root
pbuilder --create --distribution jessie --architecture armhf --debootstrap qemu-debootstrap
```

```
# create package
pdebuild --architecture armhf -- --basetgz /var/cache/pbuilder/base-armhf.tgz >output.log 2>&1
```

```
# build bbb image
build_bbb_image.sh /var/cache/pbuilder/result/honeysens-sensor_0.1_armhf.deb
```
