# Server
The server component provides a central REST API to both the sensors as well as users, which interact with it via an extensive web interface. 

## Build
The server can be run either in development or production mode. For a deployment in either mode, a recent installation of the [Docker Engine](https://www.docker.com/products/docker-engine), GNU make and curl on top of any Linux installation are the only requirements. The build process relies on Docker Compose, which will be fetched automatically and written to the build directory (`out/`).

To initiate the build process, launch make from within the `server/` directory in one of the following ways:
* `make dev`: Builds and launches a development server system that continuously watches the local codebase for changes and automatically deploys those to the running dev instance. By default, only the port 443 (HTTPS) is published to the host system for easier access. Modify `docker-compose-dev.yml` if you want to change that behaviour. Use `Strg+C` from the terminal to stop a running dev server.
* `make dist` (default) will build and save a production-ready server image to `server/out/dist/`. For that, it will internally first create and launch a development image to assemble the codebase. Afterwards, the build process for the actual production server image will be launched.
* `make reset` will shut down the development server and remove associated volumes, thus resetting that instance.
* `make clean` can be used to shut down the development server, remove associated volumes and all build artifacts (including the development docker image). However, this command won't clean the entire codebase, so that PHP dependencies don't have to be re-downloaded for each new build process.

After the build process is complete, the resulting Docker images `honeysens/server-dev` and `honeysens/server` are available on your system.

### Makefile variables
Several variables within the provided Makefile can be utilized to modify aspects of the build process.
* `PREFIX` and `REVISION` are used to label the resulting server image
* `OUTDIR` specifies the local build directory
* `BRANDING_ENABLED` should be set to `yes` to enable MMS stylesheets, and to `no` to build with the default styles
* `DEV_WATCH_TASK` selects a 'grunt watch' backend for the development server. This defaults to the resource efficient [chokidar](https://www.npmjs.com/package/grunt-chokidar), but the official default implementation [watch](https://gruntjs.com/plugins/watch) as well as [simple-watch](https://www.npmjs.com/package/grunt-simple-watch) are available as fallbacks.
* `DEV_ENV_LDAP` launches an OpenLDAP container within the development environment to test LDAP authentication. The files in `utils/dev_services/ldap/` contain further usage instructions.
* `DEV_ENV_PROXY` launches a web proxy container within a separate proxy network that can be utilized both proxy and EAPOL functionality. The files in `utils/dev_services/proxy/` contain further usage instructions.

## Deployment
First ensure that the server image has been registered on the target host (either after a build done on the same host or with `docker load`).  For the actual deployment, usage of [Docker Compose](https://docs.docker.com/compose/) is recommended. The `server/` directory contains a file `docker-compose.yml` that can be used as a blueprint for a deployment. Please consult that file and adjust as necessary, especially the `DOMAIN` environment variable which should contain the expected domain name of the server as well as the `DB_*` variables and database container configuration. Afterwards, simply `cd` to the `server/` directory and issue `docker-compose up` to initiate the deployment process.

After the server has been started, access its web interface through a web browser to perform the initial system setup. Further steps include the upload of previously built firmware and service images, as well as the registration and deployment of sensors. Documentation is distributed along with the server and can be accessed through the web interface via the *Info* module. It is currently not up to date, but still sufficient to learn the basics.

## Directory structure
* `app/`: Backend / REST API
* `conf/`: Default server configuration file
* `css/`: Frontend stylesheets
* `docs/`: Server documentation and related documents
* `js/`: Frontend web application logic
* `out/`: Build directory used by make and grunt
* `services/`: Services interfacing with the API
* `static/`: Static web data
* `utils/docker/`: Scripts used during the container build process
* `utils/`: Doctrine CLI and all the stuff that didn't fit elsewhere

