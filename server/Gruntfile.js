module.exports = function(grunt) {

    var dstPrefix = grunt.option('dst') || 'out/web',
        srcPrefix = grunt.option('src') || '.';

    grunt.loadNpmTasks('grunt-chmod');
    grunt.loadNpmTasks('grunt-composer');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-requirejs');
    grunt.loadNpmTasks('grunt-contrib-symlink');
    grunt.loadNpmTasks('grunt-latex');
    grunt.loadNpmTasks('grunt-mkdir');
    grunt.loadNpmTasks('grunt-shell');
    // Watch task backends
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-simple-watch');
    grunt.loadNpmTasks('grunt-chokidar');

    var gruntConfig = {
        pkg: grunt.file.readJSON('package.json'),
        stylesheets: [
            srcPrefix + '/css/bootstrap.css',
            srcPrefix + '/css/bootstrapValidator.css',
            srcPrefix + '/css/backgrid-paginator.css',
            srcPrefix + '/css/bootstrap-datepicker3.css',
            srcPrefix + '/css/jquery.fileupload.css',
            srcPrefix + '/css/fonts.css',
            srcPrefix + '/css/fileinput.css',
            srcPrefix + '/css/honeysens.css'],
        clean: [
            dstPrefix + '/app',
            dstPrefix + '/cache',
            dstPrefix + '/data',
            dstPrefix + '/docs',
            dstPrefix + '/public',
            dstPrefix + '/utils'],
        mkdir: {
            dist: {
                options: { create: [
                    dstPrefix + '/cache',
                    dstPrefix + '/data/upload',
                    dstPrefix + '/data/firmware',
                    dstPrefix + '/data/configs',
                    dstPrefix + '/data/CA'] }
           }
        },
        symlink: {
            options: {
                overwrite: true
            },
            composer_json: {
                src: srcPrefix + '/app/composer.json',
                dest: srcPrefix + '/out/dev/composer.json'
            },
            composer_sh: {
                src: srcPrefix + '/app/composer.sh',
                dest: srcPrefix + '/out/dev/composer.sh'
            },
            php_app: {
                src: dstPrefix + '/app',
                dest: '/opt/HoneySens/app'
            },
            php_vendor: {
                src: srcPrefix + '/out/dev/vendor',
                dest: dstPrefix + '/app/vendor'
            }
        },
        copy: {
            static: {
                expand: true,
                cwd: srcPrefix + '/static/',
                dest: dstPrefix + '/public/',
                src: ['fonts/**', 'images/**', 'docs/**']
            },
            app: {
                expand: true,
                cwd: srcPrefix,
                dest: dstPrefix + '/',
                src: ['app/**', '!app/index.php']
            },
            public: {
                expand: true,
                cwd: srcPrefix + '/app/',
                dest: dstPrefix + '/public/',
                src: ['index.php']
            },
            data: {
               files: [
                   { expand: true, cwd: srcPrefix + '/utils/', dest: dstPrefix + '/data/CA/', src: 'openssl_ca.cnf' },
                   { expand: true, cwd: srcPrefix + '/utils/', dest: dstPrefix + '/utils/', src: ['doctrine-cli.php', 'docker/**'] },
                   { expand: true, cwd: srcPrefix + '/conf/', dest: dstPrefix + '/data/', src: 'config.clean.cfg' }
               ]
            },
            requirejs: {
                expand: true,
                cwd: srcPrefix + '/js/',
                dest: dstPrefix + '/public/js/',
                src: 'lib/require.js',
            },
            js: {
                expand: true,
                cwd: srcPrefix + '/js/',
                dest: dstPrefix + '/public/js/',
                src: '**'
            },
            docs: {
                files: [
                    { expand: true, cwd: dstPrefix + '/docs/admin_manual/', dest: dstPrefix + '/public/docs/', src: 'admin_manual.pdf' },
                    { expand: true, cwd: dstPrefix + '/docs/user_manual/', dest: dstPrefix + '/public/docs/', src: 'user_manual.pdf' }
                ]
            },
            php_vendor: {
                expand: true,
                cwd: srcPrefix + '/out/dev/',
                dest: dstPrefix + '/app/',
                src: 'vendor/**'
            }
        },
        concat: {
            dist: {
                dest: dstPrefix + '/public/css/<%= pkg.name %>.css',
                src: '<%= stylesheets %>'
            }
        },
        requirejs: {
            dist: {
                options: {
                    baseUrl: srcPrefix + '/js/lib',
                    mainConfigFile: srcPrefix + '/js/main.js',
                    optimize: 'none',
                    out: dstPrefix + '/public/js/main.js',
                    name: 'app/main',
                    wrapShim: true
                }
            }
        },
        cssmin: {
            dist: {
                files: [{
                    dest: dstPrefix + '/public/css/<%= pkg.name %>.css',
                    src: '<%= stylesheets %>'
                }]
            }
        },
        latex: {
            admin_manual: {
                options: {
                    outputDirectory: dstPrefix + '/docs/admin_manual'
                },
                expand: true,
                cwd: srcPrefix + '/key/admin_manual/',
                src: 'admin_manual.tex'
            },
            user_manual: {
                options: {
                    outputDirectory: dstPrefix + '/docs/user_manual'
                },
                expand: true,
                cwd: srcPrefix + '/docs/user_manual/',
                src: 'user_manual.tex'
            }
        },
        chmod: {
            scripts: {
                options: {
                    mode: '755'
                },
                src: [dstPrefix + '/app/scripts/**',
                      dstPrefix + '/utils/docker/startup.d/*',
                      dstPrefix + '/utils/docker/run.sh']
            },
            data: {
                options: {
                    mode: '777'
                },
                src: [
                    dstPrefix + '/cache',
                    dstPrefix + '/data/configs',
                    dstPrefix + '/data/firmware',
                    dstPrefix + '/data/upload',
                    dstPrefix + '/data/config.clean.cfg',
                    dstPrefix + '/data']
            }
        },
        composer: {
            options: {
                composerLocation: srcPrefix + '/out/dev/composer.phar',
                usePhp: true,
                cwd: srcPrefix + '/out/dev'
            },
        },
        shell: {
            CA: {
                command: 'openssl req -nodes -new -x509 -extensions v3_ca -keyout ' + dstPrefix + '/data/CA/ca.key -out ' + dstPrefix + '/data/CA/ca.crt -days 365 -subj "/C=DE/ST=Saxony/L=Dresden/O=SID/CN=HoneySens"'
            },
            TLS: {
                command: [
                    'openssl genrsa -out ' + dstPrefix + '/data/https.key 2048',
                    'openssl req -new -key ' + dstPrefix + '/data/https.key -out ' + dstPrefix + '/data/https.csr -subj "/CN=$(hostname)"',
                    'openssl x509 -req -in ' + dstPrefix + '/data/https.csr -CA ' + dstPrefix + '/data/CA/ca.crt -CAkey ' + dstPrefix + '/data/CA/ca.key -CAcreateserial -out ' + dstPrefix + '/data/https.crt -days 365 -sha256',
                    'cat ' + dstPrefix + '/data/https.crt ' + dstPrefix + '/data/CA/ca.crt > ' + dstPrefix + '/data/https.chain.crt'
                ].join('&&')
            },
            composer: {
                command: '/bin/sh composer.sh',
                cwd: srcPrefix + '/out/dev'
            }
        },
        watch: {
            app: {
                files: [srcPrefix + '/app/**'],
                tasks: ['copy:app'],
                options: { spawn: false }
            },
            js: {
                files: [srcPrefix + '/js/**'],
                tasks: ['copy:js'],
                options: { spawn: false }
            },
            css: {
                files: '<%= stylesheets %>',
                tasks: ['concat:dist'],
                options: { spawn: false }
            }
        }
    };
    // Supply watch options to the chokidar task
    gruntConfig.chokidar = gruntConfig.watch;

    grunt.initConfig(gruntConfig);

    // Watch for changes and adjust tasks accordingly
    var changedAppFiles = Object.create(null),
        onAppChange = grunt.util._.debounce(function(path) {
            grunt.config('copy.app.src', Object.keys(changedAppFiles));
            changedAppFiles = Object.create(null);
        }, 200),
        changedJSFiles = Object.create(null),
        onJSChange = grunt.util._.debounce(function(path) {
            grunt.config('copy.js.src', Object.keys(changedJSFiles));
            changedJSFiles = Object.create(null);
        }, 200),
        watchEvent = grunt.cli.tasks.indexOf('chokidar') > -1 ? 'chokidar' : 'watch';
    grunt.event.on(watchEvent, function(action, filepath) {
        // Slice the source prefix from filepath so that the resulting path lies within copy.(app|js).cwd
        filepath = filepath.slice(filepath.indexOf(srcPrefix) + srcPrefix.length + 1);
        if(grunt.file.isMatch('app/**', filepath)  ) {
            if(!grunt.file.isMatch('app/index.php', filepath)) {
                changedAppFiles[filepath] = action;
            }
            onAppChange();
        } else if(grunt.file.isMatch('js/**', filepath)) {
            // Slice 'js/' from filepath
            changedJSFiles[filepath.slice(3)] = action;
            onJSChange();
        }
    });

    grunt.registerTask('docs', [
        'latex',
        'latex', // Invoke pdflatex a second time for indexing and layouting
        'copy:docs'
    ]);
    grunt.registerTask('default', [
        'mkdir',
        'copy:static',
        'copy:app',
        'copy:public',
        'copy:data',
        'copy:requirejs',
        'copy:js',
        'symlink:composer_json',
        'symlink:composer_sh',
        'symlink:php_app',
        'shell:composer',
        'composer:install',
        'symlink:php_vendor',
        'concat:dist',
        'chmod'
    ]);
    grunt.registerTask('release', [
        'clean',
        'mkdir',
        'docs',
        'copy:static',
        'copy:app',
        'copy:public',
        'copy:data',
        'copy:requirejs',
        'symlink:composer_json',
        'symlink:composer_sh',
        'shell:composer',
        'composer:install',
        'copy:php_vendor',
        'requirejs',
        'cssmin:dist',
        'chmod'
    ]);
};
