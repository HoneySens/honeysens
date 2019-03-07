PREFIX := HoneySens-Firmware-dockerx86
REVISION := 1.1.0
OUTDIR := out
METADATA := metadata.xml
# (watch|simple-watch|chokidar)
export DEV_WATCH_TASK := chokidar

dist: ${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz

clean: ${OUTDIR}/utils/docker-compose
	${OUTDIR}/utils/docker-compose -f docker-compose-dev.yml -p sensor_dev down -v
	docker rmi sensor_dev_sensor:latest || true
	rm -rf ${OUTDIR}

dev: ${OUTDIR}/utils/docker-compose
	${OUTDIR}/utils/docker-compose -f docker-compose-dev.yml -p sensor_dev up || true

${OUTDIR}/utils/docker-compose: | DIR.${OUTDIR}/utils
	curl -L "https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$$(uname -s)-$$(uname -m)" -o $@
	chmod +x $@
	$@ --version

${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz: ${OUTDIR}/build/${PREFIX}-${REVISION}/firmware.img ${OUTDIR}/build/${PREFIX}-${REVISION}/metadata.xml ${OUTDIR}/build/${PREFIX}-${REVISION}/docker-compose.yml | DIR.${OUTDIR}/dist
	mkdir -p ${OUTDIR}/build/${PREFIX}-${REVISION}/conf
	cp -v .env README.md ${OUTDIR}/build/${PREFIX}-${REVISION}/
	tar -czf $@ -C ${OUTDIR}/build/ ${PREFIX}-${REVISION}/

${OUTDIR}/build/${PREFIX}-${REVISION}/firmware.img: | DIR.${OUTDIR}/build/${PREFIX}-${REVISION}
	docker build --build-arg REVISION=${REVISION} -t honeysens/sensorx86:${REVISION} -f Dockerfile ../../
	docker save -o $@ honeysens/sensorx86:${REVISION}

${OUTDIR}/build/${PREFIX}-${REVISION}/metadata.xml: | DIR.${OUTDIR}/build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' metadata.xml > $@

${OUTDIR}/build/${PREFIX}-${REVISION}/docker-compose.yml: | DIR.${OUTDIR}/build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' docker-compose.yml > $@

DIR.${OUTDIR}/%:
	mkdir -p $(@:DIR.%=%)
