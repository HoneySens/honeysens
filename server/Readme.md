# Server
A HoneySens server consists of several *core services*, running as containerized components, that work in tandem to serve a REST API, a web frontend and a Docker registry to sensors and users:
* [backup](services/backup/Readme.md): Creates and restores backups; Can reset a deployment to post-installation state.
* broker: Redis-based volatile key-value store, used as temporary storage and communication channel by other components.
* database: SQL-based backend database, used by most other components.
* registry: Docker registry, distributes and service images to sensors. The registry is managed via the web frontend.
* [tasks](services/tasks/Readme.md): Services an internal task queue handling long-running and background tasks.
* [web](services/web/Readme.md): Serves both a REST API and web frontend, used by both sensors and users.

In addition, several *development services* can be launched in a development environment to simulate real-world conditions in the lab:
* [ldap](dev_services/ldap/Readme.md): Adds an OpenLDAP server to the dev environment to test LDAP user authentication.
* [proxy](dev_services/proxy/Readme.md): Adds both an HTTP-based web proxy and an EAPOL authenticator to the dev environment to test sensor communication via proxy and sensor EAPOL authentication.
* [syslog](dev_services/syslog/Readme.md): Adds a syslog server to the dev environment to test event forwarding.

Refer to the linked service subdirectories for further details. Broker, database and registry are canonical images taken from upstream sources such as the Docker Hub.

## Build
All server services can be built from this top-level directory via a Makefile that subsequently executes Makefiles within the service directories. The server can be run either in development or production mode. For a deployment in either mode, a recent installation of [Docker Engine](https://docs.docker.com/engine/), GNU make and curl are required. Building has only been tested on Linux hosts.

To initiate the build process, adjust `Makefile` as necessary, then launch make from within this directory in one of the following fashions:
* `make dev`: Builds and launches a development server that continuously watches the local codebase for changes and automatically deploys those to the running instance. By default, only the port 443 (HTTPS) is published to the host system for easier access. Modify `docker-compose-dev.yml` if to change that behaviour. Use `Strg+C` from the terminal to stop a running dev server.
* `make dist` (default) will build and write a production-ready server image to `server/build/dist/`.
* `make reset` shuts down the development server and removes associated volumes, thus resetting that instance.
* `make clean` can be used to shut down the development server, remove associated volumes and all build artifacts (including the development docker image).

### Makefile variables
Several variables within the `Makefile` can be utilized to modify aspects of the build process.
* `PREFIX` and `REVISION` are used to label and tag the resulting server images when doing `make dist`. A build ID based on the current date is automatically appended to the revision string.
* `DEV_ENV_LDAP` launches an OpenLDAP container within the development environment to test LDAP authentication. Refer to [dev_services/ldap/Readme.md](dev_services/ldap/Readme.md) for further instructions.
* `DEV_ENV_PROXY` launches a web proxy container within a separate proxy network that can be utilized both proxy and EAPOL functionality. Refer to [dev_services/proxy/Readme.md](dev_services/proxy/Readme.md) for further instructions.
* `DEV_ENV_SYSLOG` launches a syslog container within the development environment to test event forwarding. Refer to [dev_services/syslog/Readme.md](dev_services/syslog/Readme.md) for further instructions.

## Deployment
For a deployment in production, first ensure that all server images have been registered on the target host, either after a build was done on the local host or manually with `docker load` after transferring the images. For the actual deployment, the usage of `docker compose` or Kubernetes is recommended. The `deployment` directory contains adjustable blueprints for both. 

In preparation, specifically check and adjust the following environment variables to your specific requirements: the `DOMAIN` environment variable of the *web* service should contain the DNS domain name of the server (from the perspective of sensors). Also update the `HS_DB_*` variables with new secrets and synchronize them with the `MYSQL_` environment variables passed to the *database* service. When done, a `docker compose up -d` launches all services and starts the deployment process (usage of k8s will obviously differ).

After the server has been started, access its web interface through a web browser to perform the initial system setup. Further steps include the upload of previously built firmware and service images, as well as the registration and deployment of sensors. Documentation for these user-facing tasks performed in the frontend can be found on [honeysens.org](https://honeysens.org/docs).
