PREFIX := HoneySens-dionaea
REVISION := 2.7.0
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
	
${OUTDIR}/images/amd64/service.tar: | ${OUTDIR}/images/amd64
	docker build --no-cache -t honeysens/dionaea:amd64-${REVISION} -f Dockerfile .
	docker save -o $@ honeysens/dionaea:amd64-${REVISION}

${OUTDIR}/images/armhf/service.tar: | ${OUTDIR}/images/armhf
	docker run --rm --privileged tonistiigi/binfmt --install linux/arm
	docker build --no-cache -t honeysens/dionaea:armhf-${REVISION} --platform linux/arm/v7 .
	docker run --rm --privileged tonistiigi/binfmt --uninstall linux/arm
	docker save -o $@ honeysens/dionaea:armhf-${REVISION}

${OUTDIR}/images/amd64/metadata.xml: | ${OUTDIR}/images/amd64
	sed 's/$$ARCH/amd64/; s/$$REVISION/${REVISION}/' metadata.xml > $@

${OUTDIR}/images/armhf/metadata.xml: | ${OUTDIR}/images/armhf
	sed 's/$$ARCH/armhf/; s/$$REVISION/${REVISION}/' metadata.xml > $@

${OUTDIR}/%:
	mkdir -p $@	
