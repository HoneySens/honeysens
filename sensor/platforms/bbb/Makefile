PREFIX := HoneySens-Firmware-BBB
# Either '2gb' (older models) or '4gb' (retail models)
IMG_SIZE := 4gb
REVISION := ${IMG_SIZE}-18.12.01
TIME := $(shell date +%Y-%m-%d)
ARCH := $(shell uname -m)
OUTDIR := out

default: ${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz

clean:
	rm -rf ${OUTDIR}

${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz: ${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/firmware-${IMG_SIZE}.img ${OUTDIR}/build/metadata.xml | DIR.${OUTDIR}/dist
	tar -czf $@ --transform 's|firmware-${IMG_SIZE}.img|firmware.img|' -C ${OUTDIR}/build/ metadata.xml -C omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/ firmware-${IMG_SIZE}.img

${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/firmware-${IMG_SIZE}.img: ${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/armhf-rootfs-debian-stretch.tar
	cd ${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/ && ./setup_sdcard.sh --img-${IMG_SIZE} firmware --dtb beaglebone --hostname sensor --enable-cape-universal --enable-fat-partition --emmc-flasher

${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/armhf-rootfs-debian-stretch.tar: ${OUTDIR}/build/omap-image-builder | DIR.${OUTDIR}/build/image-root
	cd ${OUTDIR}/build/omap-image-builder && ./RootStock-NG.sh -c bb.org-debian-stretch-console-v4.9
	patch -d ${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME} -p1 < setup_sdcard.patch
	tar -xf $@ -C ${OUTDIR}/build/image-root
	patch -d ${OUTDIR}/build/image-root -p1 < ssh-over-usb-fix.patch
	cp -v bbb-flasher.patch ${OUTDIR}/build/omap-image-builder/deploy/debian-9.3-console-armhf-${TIME}/
	cp -vr ../../manager ${OUTDIR}/build/image-root/opt/
	cp -v prepare.sh ${OUTDIR}/build/image-root/root/
	cp -v manager.service ${OUTDIR}/build/image-root/etc/systemd/system/
	mount -t proc proc ${OUTDIR}/build/image-root/proc/
	mount -t sysfs sys ${OUTDIR}/build/image-root/sys/
	mount -o bind /dev ${OUTDIR}/build/image-root/dev/
	chroot ${OUTDIR}/build/image-root/ /root/prepare.sh "${REVISION}"
	umount ${OUTDIR}/build/image-root/proc ${OUTDIR}/build/image-root/sys ${OUTDIR}/build/image-root/dev
	tar -cf $@ -C ${OUTDIR}/build/image-root/ .
	rm -rf ${OUTDIR}/build/image-root

${OUTDIR}/build/omap-image-builder: | DIR.${OUTDIR}/build
	test ${ARCH} = "armv7l" || { echo "Error: This firmware can only be built on ARMv7 architectures"; exit 1; }
	git clone --branch v2018.01 https://github.com/RobertCNelson/omap-image-builder ${OUTDIR}/build/omap-image-builder

${OUTDIR}/build/metadata.xml: | DIR.${OUTDIR}/build
	sed 's/$$REVISION/${REVISION}/' metadata.xml > $@

DIR.${OUTDIR}/%:
	mkdir -p $(@:DIR.%=%)
