PREFIX := HoneySens-Firmware-dockerx86
REVISION := 1.0.4
OUTDIR := out
METADATA := metadata.xml

default: ${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz

clean:
	rm -rf ${OUTDIR}

${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz: ${OUTDIR}/build/firmware.img ${OUTDIR}/build/metadata.xml | DIR.${OUTDIR}/dist
	cp -v launcher/launch.py ${OUTDIR}/build/
	tar -czf $@ -C ${OUTDIR}/build/ firmware.img metadata.xml launch.py

${OUTDIR}/build/firmware.img: | DIR.${OUTDIR}/build
	docker build --build-arg REVISION=${REVISION} -t honeysens/sensorx86:${REVISION} -f Dockerfile ../../
	docker save -o $@ honeysens/sensorx86:${REVISION}

${OUTDIR}/build/metadata.xml: | DIR.${OUTDIR}/build
	sed 's/$$REVISION/${REVISION}/' metadata.xml > $@

DIR.${OUTDIR}/%:
	mkdir -p $(@:DIR.%=%)
