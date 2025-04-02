BUILD_ID := $(shell date +%Y%m%d)
REVISION := dev-${BUILD_ID}

dist: build/dist/tasks-${REVISION}.tar

clean:
	rm -rf build

build/dist/tasks-${REVISION}.tar: | DIR.build/dist
	docker build --no-cache --build-arg BID=${BUILD_ID} -t honeysens/tasks:${REVISION} .
	docker save -o $@ honeysens/tasks:${REVISION}

DIR.build/%:
	mkdir -p $(@:DIR.%=%)
