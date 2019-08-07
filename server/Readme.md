## Directory structure
* `app/`: Backend / REST API
* `conf/`: Default server configuration file
* `css/`: Frontend stylesheets
* `docs/`: Server documentation and related documents
* `js/`: Frontend web application logic
* `out/`: Build directory used by make and grunt
* `static/`: Static web data
* `tasks/`: Task processor that interfaces with an beanstalk instance
* `utils/docker/`: Scripts used during the container build process
* `utils/`: Doctrine CLI and all the stuff that didn't fit elsewhere

## Makefile variables
Several variables within the provided Makefile can be utilized to modify aspects of the build process.
* `PREFIX` and `REVISION` are used to label the resulting server image
* `OUTDIR` specifies the local build directory
* `BRANDING_ENABLED` should be set to `yes` to enable MMS stylesheets, and to `no` to build with the default styles
* `DEV_WATCH_TASK` selects a 'grunt watch' backend for the development server. This defaults to the resource efficient [chokidar](https://www.npmjs.com/package/grunt-chokidar), but the official default implementation [watch](https://gruntjs.com/plugins/watch) as well as [simple-watch](https://www.npmjs.com/package/grunt-simple-watch) are available as fallbacks.
* `DEV_ENV_LDAP` launches an OpenLDAP container within the development environment to test LDAP authentication. The files in `utils/dev_services/ldap/` contain further usage instructions.