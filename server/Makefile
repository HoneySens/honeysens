PREFIX := HoneySens-Server
BUILD_ID := $(shell date +%Y%m%d)
REVISION := 2.8.0-${BUILD_ID}

# Additional dev environment features
DEV_ENV_LDAP := no
DEV_ENV_PROXY := no
DEV_ENV_SYSLOG := no

dist: build/dist/${PREFIX}-${REVISION}.tar.gz

clean: reset
	docker rmi honeysens_dev-backup:latest honeysens_dev-tasks:latest honeysens_dev-web:latest 2>/dev/null || true
	rm -rf dev_services/proxy/ca/ca* dev_services/proxy/ca/client* dev_services/proxy/ca/server*
	rm -rf build
	$(MAKE) -C services/backup clean
	$(MAKE) -C services/tasks clean
	$(MAKE) -C services/web clean

dev:
	docker compose --project-directory $(shell pwd) -f deployment/docker/docker-compose-dev.yml $(if $(DEV_ENV_LDAP:no=),-f dev_services/ldap/docker-compose.yml,) $(if $(DEV_ENV_PROXY:no=),-f dev_services/proxy/docker-compose.yml,) $(if $(DEV_ENV_SYSLOG:no=),-f dev_services/syslog/docker-compose.yml,) -p honeysens_dev up || true

reset:
	docker compose --project-directory $(shell pwd) -f deployment/docker/docker-compose-dev.yml $(if $(DEV_ENV_LDAP:no=),-f dev_services/ldap/docker-compose.yml,) $(if $(DEV_ENV_PROXY:no=),-f dev_services/proxy/docker-compose.yml,) $(if $(DEV_ENV_SYSLOG:no=),-f dev_services/syslog/docker-compose.yml,) -p honeysens_dev down -v || true

services/backup/build/dist/backup-${REVISION}.tar:
	$(MAKE) -C services/backup dist BUILD_ID=${BUILD_ID} REVISION=${REVISION}

services/backup/build/dist/tasks-${REVISION}.tar:
	$(MAKE) -C services/tasks dist BUILD_ID=${BUILD_ID} REVISION=${REVISION}

services/backup/build/dist/web-${REVISION}.tar:
	$(MAKE) -C services/web dist BUILD_ID=${BUILD_ID} REVISION=${REVISION}

build/dist/${PREFIX}-${REVISION}.tar.gz: services/backup/build/dist/backup-${REVISION}.tar services/backup/build/dist/tasks-${REVISION}.tar services/backup/build/dist/web-${REVISION}.tar build/${PREFIX}-${REVISION}/docker-compose.yml | DIR.build/dist
	cp -vr docs/INSTALL build/${PREFIX}-${REVISION}/
	ln -vfs ../../services/backup/build/dist/backup-${REVISION}.tar build/${PREFIX}-${REVISION}/backup-${REVISION}.tar
	ln -vfs ../../services/tasks/build/dist/tasks-${REVISION}.tar build/${PREFIX}-${REVISION}/tasks-${REVISION}.tar
	ln -vfs ../../services/web/build/dist/web-${REVISION}.tar build/${PREFIX}-${REVISION}/web-${REVISION}.tar
	tar -czhf $@ -C build/ ${PREFIX}-${REVISION}/

build/${PREFIX}-${REVISION}/docker-compose.yml: | DIR.build/${PREFIX}-${REVISION}
	sed 's/$$REVISION/${REVISION}/' deployment/docker/docker-compose.yml > $@

DIR.build/%:
	mkdir -p $(@:DIR.%=%)
