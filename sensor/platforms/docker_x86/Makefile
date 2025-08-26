PREFIX := HoneySens-Firmware-dockerx86
REVISION := 2.9.1
METADATA := metadata.xml

dist: build/dist/${PREFIX}-${REVISION}.tar.gz

clean: reset
	docker rmi honeysens_dev_sensor:latest || true
	rm -rf build

dev:
	docker compose -f docker-compose-dev.yml -p honeysens_dev up || true

reset:
	docker compose -f docker-compose-dev.yml -p honeysens_dev down -v || true

build/dist/${PREFIX}-${REVISION}.tar.gz: build/${PREFIX}-${REVISION}/firmware.img build/${PREFIX}-${REVISION}/metadata.xml build/${PREFIX}-${REVISION}/docker-compose.yml | DIR.build/dist
	mkdir -p build/${PREFIX}-${REVISION}/conf
	cp -v .env Deployment.md build/${PREFIX}-${REVISION}/
	tar -czf $@ -C build/${PREFIX}-${REVISION}/ firmware.img metadata.xml docker-compose.yml conf/ .env Deployment.md

build/${PREFIX}-${REVISION}/firmware.img: | DIR.build/${PREFIX}-${REVISION}
	docker build --no-cache --build-arg REVISION=${REVISION} -t honeysens/sensorx86:${REVISION} -f Dockerfile ../../
	docker save -o $@ honeysens/sensorx86:${REVISION}

build/${PREFIX}-${REVISION}/metadata.xml: | DIR.build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' metadata.xml > $@

build/${PREFIX}-${REVISION}/docker-compose.yml: | DIR.build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' docker-compose.yml > $@

DIR.build/%:
	mkdir -p $(@:DIR.%=%)
