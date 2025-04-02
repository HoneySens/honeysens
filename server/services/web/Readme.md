# Web service
Serves a PHP-based REST API and a JavaScript-based web frontend, which are the primary endpoints for both users and sensors.

## Build notes
The build process is best managed through the top-level `Makefile` in `server/`. When building a distribution package via `make dist`, this service behaves differently from the others: First, a development image tagged as `honeysens/web-builder` will be built. This image is identical to the regular dev environment, but will then be launched with the environment variable `BUILD_ONLY=1`, which instructs the dev container to download all external dependencies and build both the API as well as the frontend project in their respective `api/build` and `frontend/build` directories. Only afterwards and with these dependencies satisfied, the final image build process can be launched. After its completion, the temporary Docker image `honeysens/web-builder` should have been removed automatically.

## Directory structure
* `api/app`: REST API sources
* `api/conf/config.clean.cfg`: Default server configuration template
* `api/utils/`: Doctrine CLI and other utility scripts
* `env`: Scripts and config files used during the container build process
* `frontend/app`: Frontend application sources
* `frontend/assets`: Static assets such as stylesheets, fonts, i18n translations and images
* `frontend/vendor`: External dependencies not available via npm