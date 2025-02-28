PREFIX := HoneySens-miniprint
REVISION := 2.3.0
OUTDIR := build
METADATA := metadata.xml

default: all

clean: 
	rm -rf ${OUTDIR}

all: ${OUTDIR}/dist/${PREFIX}-amd64-${REVISION}.tar.gz ${OUTDIR}/dist/${PREFIX}-armhf-${REVISION}.tar.gz
amd64: ${OUTDIR}/dist/${PREFIX}-amd64-${REVISION}.tar.gz
armhf: ${OUTDIR}/dist/${PREFIX}-armhf-${REVISION}.tar.gz

${OUTDIR}/dist/${PREFIX}-amd64-${REVISION}.tar.gz: ${OUTDIR}/images/amd64/service.tar ${OUTDIR}/images/amd64/metadata.xml | ${OUTDIR}/dist
	tar -czf $@ -C ${OUTDIR}/images/amd64/ service.tar metadata.xml

${OUTDIR}/dist/${PREFIX}-armhf-${REVISION}.tar.gz: ${OUTDIR}/images/armhf/service.tar ${OUTDIR}/images/armhf/metadata.xml | ${OUTDIR}/dist
	tar -czf $@ -C ${OUTDIR}/images/armhf/ service.tar metadata.xml

${OUTDIR}/images/amd64/service.tar: Dockerfile server.patch honeysens.py prepare.sh | ${OUTDIR}/images/amd64
	docker build -t honeysens/miniprint:amd64-${REVISION} .
	docker save -o $@ honeysens/miniprint:amd64-${REVISION}

${OUTDIR}/images/armhf/service.tar: server.patch honeysens.py prepare.sh | ${OUTDIR}/images/armhf
	docker run --rm --privileged multiarch/qemu-user-static:register || true
	docker build -t honeysens/miniprint:armhf-${REVISION} --build-arg ARCH=armhf .
	docker run --rm --privileged multiarch/qemu-user-static:register --reset
	docker save -o $@ honeysens/miniprint:armhf-${REVISION}

${OUTDIR}/images/amd64/metadata.xml: metadata.xml | ${OUTDIR}/images/amd64
	sed 's/$$ARCH/amd64/; s/$$REVISION/${REVISION}/' metadata.xml > $@

${OUTDIR}/images/armhf/metadata.xml: metadata.xml | ${OUTDIR}/images/armhf
	sed 's/$$ARCH/armhf/; s/$$REVISION/${REVISION}/' metadata.xml > $@

${OUTDIR}/%:
	mkdir -p $@
