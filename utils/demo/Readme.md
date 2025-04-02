# HoneySens Demo Environment
Scripts to build and initialize a minimal HoneySens deployment that consists of a server and one attached dockerized sensor running a few services.

To build docker images for such a demo environment, invoke

`create_demo.sh server_revision sensor_revision`

where `server_revision` and `sensor_revision` are passed to the server and sensor Makefiles and primarily influence the tags of resulting Docker images and versions shown in the web interface. Regardless of their values, the script will always build images from the state of the current source tree. The script will 
* create a `build` directory for temporary and final (result) fragments
* build all required components (server, sensor and service images)
* run, initialize and configure a temporary server deployment
* take a snapshot from that deployment
* build and save an initializer image tagged as `honeysens/demo-init:${BUILD_ID}` that contains the snapshot and a sensor configuration archive
* remove the temporary server deployment and its fragments in `build/`

If successful, the `build/` directory contains a `docker-compose.yml` to run the demo deployment and a `demo-init-${BUILD_ID}.tar` Docker image with the initializer image. All other images (server, sensor and services) are in their respective `build` directories within the source tree.

When launching the demo deployment via `docker compose up` or similar, the `depends_on` section within the `docker-compose.yml` ensures that the database starts up first, followed by the `demo-init` service which restores the snapshot created previously to the database and data volumes. After that, the remaining services launch, which also includes a sensor instance (all within the same Docker network). The web interface can then be visited at [https://localhost](https://localhost).

Defaults such as the admin password, default group name, sensor name or sensor services are defined at the top of `create_demo.sh` and can be freely modified. Be aware that adding more services may increase the size of the resulting `demo-init` image significantly.