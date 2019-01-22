PREFIX := HoneySens-Server
REVISION := 18.12.01
OUTDIR := out

dist: ${OUTDIR}/dist/${PREFIX}-${REVISION}.tar

clean: ${OUTDIR}/dev/docker-compose
	${OUTDIR}/dev/docker-compose -f docker-compose-dev.yml -p honeysens_dev down -v
	docker rmi honeysens_dev_server:latest
	rm -rf ${OUTDIR}

dev: ${OUTDIR}/dev/docker-compose
	${OUTDIR}/dev/docker-compose -f docker-compose-dev.yml -p honeysens_dev up || true

${OUTDIR}/dev/docker-compose: | DIR.${OUTDIR}/dev
	curl -L "https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$$(uname -s)-$$(uname -m)" -o $@
	chmod +x $@
	$@ --version

${OUTDIR}/dist/${PREFIX}-${REVISION}.tar: ${OUTDIR}/web/data/config.cfg | DIR.${OUTDIR}/dist
	docker build -t honeysens/server:${REVISION} .
	docker save -o $@ honeysens/server:${REVISION}

${OUTDIR}/web/data/config.cfg: | DIR.$(OUTDIR)/web
	docker build -t honeysens/server-dev:${REVISION} -f Dockerfile.dev .
	docker run -v $(shell pwd):/mnt -v $(shell pwd)/${OUTDIR}/web:/srv -e BUILD_ONLY=1 --rm honeysens/server-dev:${REVISION} || true
	cp ${OUTDIR}/web/data/config.clean.cfg ${OUTDIR}/web/data/config.cfg

DIR.${OUTDIR}/%:
	mkdir -p $(@:DIR.%=%)
