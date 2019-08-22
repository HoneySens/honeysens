## Backup service
This directory contains the source for the backup service container, which can be utilized to create a backup archive of the current deployment (during runtime), restore a backup that was made previously or to perform a factory reset. The container's init process just sleeps by default, actual commands can be run from within the container (the <em>Usage</em> below). To perform its tasks, this container has access to all data sources of the current deployment by either bind mounts or via the internal docker network. The default Compose file already contains the required parameters to make that work out of the box.

### Usage
All commands have to be executed from within the container. For this, both `docker exec` or `docker-compose exec` are sufficient.

The available commands are:

* **backup**: Writes a compressed backup archive (`.tar.bz2`) to stdout that contains all volume and database contents. Shoule be piped into a file (see <em>Examples</em> below).
* **restore**: Reads a compressed backup archive (`.tar.bz2`) from stdin and restores it on the current deployment. All services except the database and backup containers have to be brought down manually beforehand.
* **reset**: Performs a factory reset by removing all volume and database contents. All services except the database and backup containers have to be brought down manually beforehand.

### Examples
The following examples utilize docker-compose and shoule be run from within a directory that contains the `docker-compose.yml` for the current deployment.

To take a snapshot from the currently running deployment, issue

`docker-compose exec -T backup backup > backup_archive.tar.bz2`

The `-T` switch is strictly required so that no interactive terminal session is launched. The backup script will pipe the resulting archive directly to stdout, which is why that can be directly redirected into a file anywhere on the host system.

To restore a snapshot, first shut down all services besides the backup and database containers:

`docker-compose stop server honeysens-registry`

then simply pipe a backup archive into the restoration script:

`cat backup_archive.tar.bz2 | docker-compose exec -T backup restore`

That script will first make sure that all services besides the databases are offline, verify the archive contents and finally restore the given snapshot. Afterwards the remaining services have to be started again:

`docker-compose start server honeysens-registry`

The procedure for doing a factory reset are similar to the restoration case, the only difference being the restoration command:

`docker-compose exec -T backup reset`
