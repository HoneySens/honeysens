BUILD_ID := $(shell date +%Y%m%d)
REVISION := dev-${BUILD_ID}

dist: build/dist/web-${REVISION}.tar

clean:
	rm -rf build api/build frontend/build

build/dist/web-${REVISION}.tar: | api/build/vendor frontend/build/dist DIR.build/dist
	docker build --no-cache --build-arg BID=${BUILD_ID} -t honeysens/web:${REVISION} -f Dockerfile .
	docker save -o $@ honeysens/web:${REVISION}

api/build/vendor frontend/build/dist:
	mkdir -p api/build/vendor frontend/build/dist
	docker build --no-cache -t honeysens/web-builder:${REVISION} -f Dockerfile.dev .
	docker run -v $(shell pwd):/mnt -e BUILD_ONLY=1 --rm honeysens/web-builder:${REVISION} || true
	docker rmi honeysens/web-builder:${REVISION}

DIR.build/%:
	mkdir -p $(@:DIR.%=%)
