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
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-latex');
    grunt.loadNpmTasks('grunt-mkdir');
    grunt.loadNpmTasks('grunt-shell');
    // grunt-simple-watch is a replacement for grunt-contrib-watch that utilizes a different polling mechanism
    grunt.loadNpmTasks('grunt-simple-watch');

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        stylesheets: [
            srcPrefix + '/css/bootstrap.css',
            srcPrefix + '/css/bootstrapValidator.css',
            srcPrefix + '/css/backgrid-paginator.css',
            srcPrefix + '/css/jquery.fileupload.css',
            srcPrefix + '/css/fonts.css',
            srcPrefix + '/css/honeysens.css'],
        clean: [dstPrefix, srcPrefix + '/app/vendor', srcPrefix + '/app/composer.phar'],
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
        copy: {
            static: {
                expand: true,
                cwd: srcPrefix + '/static/',
                dest: dstPrefix + '/public/',
                src: ['fonts/**', 'images/**', 'docs/**', '.htaccess']
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
            beanstalk: {
                expand: true,
                cwd: srcPrefix + '/utils/',
                dest: dstPrefix + '/',
                src: 'beanstalk/**'
            },
            requirejs: {
                expand: true,
                cwd: srcPrefix + '/js/',
                dest: dstPrefix + '/public/js/',
                src: 'lib/require.js'
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
                cwd: srcPrefix + '/docs/admin_manual/',
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
                src: [dstPrefix + '/app/scripts/**', dstPrefix + '/utils/docker/my_init.d/*.sh', dstPrefix + '/utils/docker/my_init.pre_shutdown.d/*.sh', dstPrefix + '/utils/docker/services/*/run']
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
                cwd: srcPrefix + '/app',
                usePhp: true,
                composerLocation: 'composer.phar'
            }
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
                command: 'cd ' + srcPrefix + '/app && /bin/sh composer.sh'
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
    });

    // only work on updated files
    var changedAppFiles = Object.create(null),
        onAppChange = grunt.util._.debounce(function(path) {
            grunt.config('copy.app.src', Object.keys(changedAppFiles));
            changedAppFiles = Object.create(null);
        }, 200),
        changedJSFiles = Object.create(null),
        onJSChange = grunt.util._.debounce(function(path) {
            grunt.config('copy.js.src', Object.keys(changedJSFiles));
            changedJSFiles = Object.create(null);
        });
    grunt.event.on('watch', function(action, filepath) {
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
        'shell:composer',
        'composer:install',
        'copy:static',
        'copy:app',
        'copy:public',
        'copy:data',
        'copy:beanstalk',
        'copy:requirejs',
        'copy:js',
        'concat:dist',
        'chmod'
    ]);
    grunt.registerTask('release', [
        'clean',
        'mkdir',
        'shell:composer',
        'composer:install',
        'docs',
        'copy:static',
        'copy:app',
        'copy:public',
        'copy:data',
        'copy:beanstalk',
        'copy:requirejs',
        'requirejs',
        'cssmin:dist',
        'chmod'
    ]);
};
