PREFIX := HoneySens-Firmware-BBB
# Either '2gb' (older models) or '4gb' (retail models)
IMG_SIZE := 4gb
REVISION := ${IMG_SIZE}-2.6.0
TIME := $(shell date +%Y-%m-%d)
ARCH := $(shell uname -m)
OUTDIR := out

default: ${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz

clean:
	rm -rf ${OUTDIR}

${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz: ${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/firmware-${IMG_SIZE}.img ${OUTDIR}/build/metadata.xml | DIR.${OUTDIR}/dist
	tar -czf $@ --transform 's|firmware-${IMG_SIZE}.img|firmware.img|' -C ${OUTDIR}/build/ metadata.xml -C omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/ firmware-${IMG_SIZE}.img

${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/firmware-${IMG_SIZE}.img: ${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/armhf-rootfs-debian-bullseye.tar
	cd ${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/ && ./setup_sdcard.sh --img-${IMG_SIZE} firmware --dtb beaglebone --hostname sensor --enable-cape-universal --enable-fat-partition --emmc-flasher

${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/armhf-rootfs-debian-bullseye.tar: ${OUTDIR}/build/omap-image-builder | DIR.${OUTDIR}/build/image-root
	cd ${OUTDIR}/build/omap-image-builder && ./RootStock-NG.sh -c bb.org-debian-bullseye-minimal-v5.10-ti-armhf.conf
	patch -d ${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME} -p1 < setup_sdcard.patch
	tar -xf $@ -C ${OUTDIR}/build/image-root
	cp -v bbb-flasher.patch ${OUTDIR}/build/omap-image-builder/deploy/debian-11.6-minimal-armhf-${TIME}/
	cp -vr ../../manager usb_audit/usb_audit.sh ${OUTDIR}/build/image-root/opt/
	cp -v prepare.sh usb_gadget_down.sh usb_gadget_up.sh ${OUTDIR}/build/image-root/root/
	cp -v manager.service usb_audit/usb-audit@.service ${OUTDIR}/build/image-root/etc/systemd/system/
	cp -v usb_audit/90-usb-audit.rules ${OUTDIR}/build/image-root/etc/udev/rules.d/
	cp -v eth0.network ${OUTDIR}/build/image-root/etc/systemd/network/
	mkdir -p ${OUTDIR}/build/image-root/etc/docker
	cp -v daemon.json ${OUTDIR}/build/image-root/etc/docker/
	mount -t proc proc ${OUTDIR}/build/image-root/proc/
	mount -t sysfs sys ${OUTDIR}/build/image-root/sys/
	mount -o bind /dev ${OUTDIR}/build/image-root/dev/
	rm -v ${OUTDIR}/build/image-root/etc/resolv.conf
	cp -v /etc/resolv.conf ${OUTDIR}/build/image-root/etc/resolv.conf
	chroot ${OUTDIR}/build/image-root/ /root/prepare.sh "${REVISION}"
	umount ${OUTDIR}/build/image-root/proc ${OUTDIR}/build/image-root/sys ${OUTDIR}/build/image-root/dev
	tar -cf $@ -C ${OUTDIR}/build/image-root/ .
	rm -rf ${OUTDIR}/build/image-root

${OUTDIR}/build/omap-image-builder: | DIR.${OUTDIR}/build
	test ${ARCH} = "armv7l" || { echo "Error: This firmware can only be built on ARMv7 architectures"; exit 1; }
	git clone https://github.com/RobertCNelson/omap-image-builder $@
	cd $@ && git reset --hard bfcab3b9fa2abbac81b5f7e4a1bd7c132e72edb6
	patch -d ${OUTDIR}/build/omap-image-builder -p1 < hwpack.patch

${OUTDIR}/build/metadata.xml: | DIR.${OUTDIR}/build
	sed 's/$$REVISION/${REVISION}/' metadata.xml > $@

DIR.${OUTDIR}/%:
	mkdir -p $(@:DIR.%=%)
