# Backup service
This directory contains the source for the backup service container, which can be utilized to create a backup archive of the current deployment (during runtime), restore a previously created backup or to perform a factory reset. By default, all supported tasks should be invoked from within the container (see <em>Usage</em> below). Additionally, a backup cron job can be scheduled to run periodically. The backup container has access to all data sources of the current deployment by bind mounts and through the internal docker network. The default Compose file already contains all required parameters to make that work out of the box.

## Usage
This container can both be utilized to manually backup/restore/reset the running deployment and to schedule automatic backup tasks (optional).

### Manual invocation
All manual tasks have to be executed from within the container. For this, both `docker exec` or `docker-compose exec` are sufficient.

The available commands are:

* **backup**: Writes a compressed backup archive (`.tar.bz2`) to stdout that contains all volume and database contents. Should be piped into a file (see <em>Examples</em> below).
* **restore**: Reads a compressed backup archive (`.tar.bz2`) from stdin and restores it on the current deployment. All services except the database and backup containers have to be brought down manually beforehand.
* **reset**: Performs a factory reset by removing all volume and database contents. All services except the database and backup containers have to be brought down manually beforehand.

### Scheduled backups
The scheduling of periodic backups can be controlled via the `CRON_*` environment variables within `docker-compose.yml`. By default, scheduled backups are disabled. To enable the feature set `CRON_ENABLED` to `true` and `CRON_CONDITION` to a valid cron scheduling expression (such as `0 3 * * *` to schedule a backup for every night, 3am). Please be aware that the backup container is ignorant of the current timezone and uses UTC. The variable `CRON_TEMPLATE` defaults to `backup-%s` and denotes the file name of each newly created backup archive without a suffix (`%s` will be substituted with the current date and time). If `DB_KEEP` is set to a value greater than zero, the old backups will be automatically deleted so that only ever `DB_KEEP` backups remain.

Scheduled backups will be written to `/srv/backup/` within the container, which should be bound to an external storage volume in `docker-compose.yml`.

## Database-only vs full backups
By default, full backups will be created, which include both the database and data volumes. To just back up the database itself without volume data (in case a backup procedure for the volumes already exists), either utilize the backup parameter `-d` for manual backups or set the environment variable `CRON_DBONLY` to `true` for scheduled database-only backups. The restoration script is aware of both backup types and doesn't require additional parameterization.

## Examples
The following examples utilize manual invocation with docker-compose and should be run from within a directory that contains a `docker-compose.yml` for the current deployment.

To take a live snapshot from the currently running deployment, issue

`docker-compose exec -T backup backup > backup_archive.tar.bz2`

The `-T` switch is strictly required so that no interactive terminal session is launched. The backup script will pipe the resulting archive directly to stdout, so that it can be redirected into a file anywhere on the host system.

To restore a snapshot, first shut down all services besides the backup and database containers:

`docker-compose stop server tasks broker registry`

then simply pipe a backup archive into the restoration script:

`cat backup_archive.tar.bz2 | docker-compose exec -T backup restore`

That script will first make sure that all services besides the databases are offline, verify the archive contents and finally restore the given snapshot. Afterwards the remaining services have to be started again:

`docker-compose start server tasks broker registry`

The procedure for doing a factory reset are similar to the restoration case, the only difference being the restoration command:

`docker-compose exec -T backup reset`
