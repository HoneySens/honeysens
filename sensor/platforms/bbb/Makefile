# build requirements: dosfstools git kpartx wget tree parted
PREFIX := HoneySens-Firmware-BBB
# Either '2gb' (older models) or '4gb' (retail models)
IMG_SIZE := 4gb
REVISION := ${IMG_SIZE}-2.9.1
TIME := $(shell date +%Y-%m-%d)
ARCH := $(shell uname -m)

default: build/dist/${PREFIX}-${REVISION}.tar.gz

clean:
	rm -rf build

build/dist/${PREFIX}-${REVISION}.tar.gz: build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/firmware-${IMG_SIZE}.img build/${PREFIX}-${REVISION}/metadata.xml | DIR.build/dist
	tar -czf $@ --transform 's|firmware-${IMG_SIZE}.img|firmware.img|' -C build/${PREFIX}-${REVISION}/ metadata.xml -C omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/ firmware-${IMG_SIZE}.img

build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/firmware-${IMG_SIZE}.img: build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/armhf-rootfs-debian-bookworm.tar
	cd build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/ && ./setup_sdcard.sh --img-${IMG_SIZE} firmware --dtb beaglebone --enable-cape-universal --enable-fat-partition --emmc-flasher

build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/armhf-rootfs-debian-bookworm.tar: build/${PREFIX}-${REVISION}/omap-image-builder | DIR.build/${PREFIX}-${REVISION}/image-root
	sed -i 's/^rfs_hostname.*/rfs_hostname="sensor"/' build/${PREFIX}-${REVISION}/omap-image-builder/configs/bb.org-debian-bookworm-minimal-v5.10-ti-armhf-am335x.conf
	cd build/${PREFIX}-${REVISION}/omap-image-builder && ./RootStock-NG.sh -c bb.org-debian-bookworm-minimal-v5.10-ti-armhf-am335x.conf
	patch -d build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME} -p1 < setup_sdcard.patch
	tar -xf $@ -C build/${PREFIX}-${REVISION}/image-root
	cp -v bbb-flasher.patch build/${PREFIX}-${REVISION}/omap-image-builder/deploy/debian-12.10-minimal-armhf-${TIME}/
	cp -vr ../../manager usb_audit/usb_audit.sh build/${PREFIX}-${REVISION}/image-root/opt/
	cp -v prepare.sh usb_gadget_down.sh usb_gadget_up.sh build/${PREFIX}-${REVISION}/image-root/root/
	cp -v manager.service usb_audit/usb-audit@.service build/${PREFIX}-${REVISION}/image-root/etc/systemd/system/
	cp -v usb_audit/90-usb-audit.rules build/${PREFIX}-${REVISION}/image-root/etc/udev/rules.d/
	cp -v eth0.network build/${PREFIX}-${REVISION}/image-root/etc/systemd/network/
	mkdir -p build/${PREFIX}-${REVISION}/image-root/etc/docker
	cp -v daemon.json build/${PREFIX}-${REVISION}/image-root/etc/docker/
	mount -t proc proc build/${PREFIX}-${REVISION}/image-root/proc/
	mount -t sysfs sys build/${PREFIX}-${REVISION}/image-root/sys/
	mount -o bind /dev build/${PREFIX}-${REVISION}/image-root/dev/
	rm -v build/${PREFIX}-${REVISION}/image-root/etc/resolv.conf
	cp -v /etc/resolv.conf build/${PREFIX}-${REVISION}/image-root/etc/resolv.conf
	chroot build/${PREFIX}-${REVISION}/image-root/ /root/prepare.sh "${REVISION}"
	umount build/${PREFIX}-${REVISION}/image-root/proc build/${PREFIX}-${REVISION}/image-root/sys build/${PREFIX}-${REVISION}/image-root/dev
	tar -cf $@ -C build/${PREFIX}-${REVISION}/image-root/ .
	rm -rf build/${PREFIX}-${REVISION}/image-root

build/${PREFIX}-${REVISION}/omap-image-builder: | DIR.build/${PREFIX}-${REVISION}
	test ${ARCH} = "armv7l" || { echo "Error: This firmware can only be built on ARMv7 architectures"; exit 1; }
	git clone https://github.com/RobertCNelson/omap-image-builder $@
	cd $@ && git reset --hard 61c2d33c8c912aa6cb04c282f934b3040a645d05
	patch -d build/${PREFIX}-${REVISION}/omap-image-builder -p1 < hwpack.patch

build/${PREFIX}-${REVISION}/metadata.xml: | DIR.build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' metadata.xml > $@

DIR.build/%:
	mkdir -p $(@:DIR.%=%)
