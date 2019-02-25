module.exports = function(grunt) {

    var dstPrefix = grunt.option('dst') || '/srv',
        srcPrefix = grunt.option('src') || '/mnt/manager';

    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-shell');
    // Watch task backends
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-simple-watch');
    grunt.loadNpmTasks('grunt-chokidar');

    var gruntConfig = {
        pkg: grunt.file.readJSON('package.json'),
        copy: {
            manager: {
                expand: true,
                cwd: srcPrefix,
                dest: dstPrefix,
                src: '**'
            }
        },
        shell: {
            manager_install: {
                command: 'python setup.py develop',
                options: {
                    execOptions: {
                        cwd: dstPrefix
                    }
                }
            },
            manager_restart: {
                command: [
                    'echo true > /var/run/s6/container_environment/SKIP_INIT',
                    's6-svc -wr -t -u /var/run/s6/services/manager/'
                ].join('&&')
            }
        },
        watch: {
            manager: {
                files: [srcPrefix + '/manager/**'],
                tasks: ['copy:manager', 'shell:manager_restart'],
                options: { spawn: false }
            }
        }
    };
    // Supply watch options to the chokidar task
    gruntConfig.chokidar = gruntConfig.watch;

    grunt.initConfig(gruntConfig);

    // Watch for changes and adjust tasks accordingly
    var changedManagerFiles = Object.create(null),
        onManagerChange = grunt.util._.debounce(function(path) {
            grunt.config('copy.manager.src', Object.keys(changedManagerFiles));
            changedManagerFiles = Object.create(null);
        }, 200),
        watchEvent = grunt.cli.tasks.indexOf('chokidar') > -1 ? 'chokidar' : 'watch';
    grunt.event.on(watchEvent, function(action, filepath) {
        // Slice the source prefix from filepath
        filepath = filepath.slice(filepath.indexOf(srcPrefix) + srcPrefix.length + 1);
        changedManagerFiles[filepath] = action;
        onManagerChange();
    });

    grunt.registerTask('default', [
        'copy:manager',
        'shell:manager_install'
    ]);
};