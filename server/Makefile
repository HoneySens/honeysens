PREFIX := HoneySens-Server
REVISION := 2.1.0
OUTDIR := out
# (watch|simple-watch|chokidar)
export DEV_WATCH_TASK := chokidar

# Additional dev environment features
DEV_ENV_LDAP := no

dist: ${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz

clean: ${OUTDIR}/dev/docker-compose reset
	docker rmi honeysens_dev_server:latest honeysens_dev_backup:latest
	rm -rf ${OUTDIR}

dev: ${OUTDIR}/dev/docker-compose
	${OUTDIR}/dev/docker-compose -f docker-compose-dev.yml $(if $(DEV_ENV_LDAP:no=),-f utils/dev_services/ldap/docker-compose.yml,) -p honeysens_dev up || true

reset: ${OUTDIR}/dev/docker-compose
	${OUTDIR}/dev/docker-compose -f docker-compose-dev.yml $(if $(DEV_ENV_LDAP:no=),-f utils/dev_services/ldap/docker-compose.yml,) -p honeysens_dev down -v

${OUTDIR}/dev/docker-compose: | DIR.${OUTDIR}/dev
	curl -L "https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$$(uname -s)-$$(uname -m)" -o $@
	chmod +x $@
	$@ --version

${OUTDIR}/dist/${PREFIX}-${REVISION}.tar.gz: ${OUTDIR}/build/${PREFIX}-${REVISION}/backup-${REVISION}.tar ${OUTDIR}/build/${PREFIX}-${REVISION}/server-${REVISION}.tar ${OUTDIR}/build/${PREFIX}-${REVISION}/docker-compose.yml | DIR.${OUTDIR}/dist
	cp -vr docs/INSTALL ${OUTDIR}/build/${PREFIX}-${REVISION}/
	tar -czf $@ -C ${OUTDIR}/build/ ${PREFIX}-${REVISION}/

${OUTDIR}/build/${PREFIX}-${REVISION}/backup-${REVISION}.tar: | DIR.${OUTDIR}/build/${PREFIX}-${REVISION}
	docker build -t honeysens/backup:${REVISION} services/backup/
	docker save -o $@ honeysens/backup:${REVISION}

${OUTDIR}/build/${PREFIX}-${REVISION}/server-${REVISION}.tar: ${OUTDIR}/web/data/config.cfg | DIR.${OUTDIR}/build/${PREFIX}-${REVISION}
	docker build -t honeysens/server:${REVISION} .
	docker save -o $@ honeysens/server:${REVISION}

${OUTDIR}/web/data/config.cfg: | DIR.$(OUTDIR)/web
	docker build -t honeysens/server-dev:${REVISION} -f Dockerfile.dev .
	docker run -v $(shell pwd):/mnt -v $(shell pwd)/${OUTDIR}/web:/srv -e BUILD_ONLY=1 --rm honeysens/server-dev:${REVISION} || true
	cp ${OUTDIR}/web/data/config.clean.cfg ${OUTDIR}/web/data/config.cfg

${OUTDIR}/build/${PREFIX}-${REVISION}/docker-compose.yml: | DIR.${OUTDIR}/build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' docker-compose.yml > $@

DIR.${OUTDIR}/%:
	mkdir -p $(@:DIR.%=%)
