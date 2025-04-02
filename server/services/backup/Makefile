BUILD_ID := $(shell date +%Y%m%d)
REVISION := dev-${BUILD_ID}

dist: build/dist/backup-${REVISION}.tar

clean:
	rm -rf build

build/dist/backup-${REVISION}.tar: | DIR.build/dist
	docker build --no-cache --build-arg BID=${BUILD_ID} -t honeysens/backup:${REVISION} .
	docker save -o $@ honeysens/backup:${REVISION}

DIR.build/%:
	mkdir -p $(@:DIR.%=%)
