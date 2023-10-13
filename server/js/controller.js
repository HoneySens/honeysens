define(['app/app',
        'app/models',
        'app/views/AppLayout',
        'app/views/Login',
        'app/views/Navigation',
        'app/views/Sidebar',
        'app/common/views/ModalServerError',
        'json',
        'app/modules/setup/module'],
function(HoneySens, Models, AppLayoutView, LoginView, NavigationView, SidebarView, ModalServerError, JSON) {
    HoneySens.module('Controller', function(Controller, HoneySens, Backbone, Marionette, $, _) {
        // @deprecated
        function assureAllowed(domain, action) {
            // TODO maybe don't rely on template helpers within a controller function
            if(!_.templateHelpers.isAllowed(domain, action)) {
                //Controller.doLogout();
                return false;
            } else return true;
        }

        Controller.doLogout = function() {
            HoneySens.execute('logout');
        };

        Controller.Router = Backbone.Marionette.AppRouter.extend({
            appRoutes: {
                'logout': 'doLogout'
            },
            onRoute: function() {
                HoneySens.startModule(Controller);
            }
        });

        HoneySens.addInitializer(function() {
            // Session management commands
            HoneySens.commands.setHandler('logout', function() {
                $.ajax({
                    type: 'DELETE',
                    url: 'api/sessions',
                    success: function(data) {
                        data = JSON.parse(data);
                        HoneySens.data.session.user = new Models.User(data.user);
                        // clear models
                        HoneySens.data.models.certs.reset();
                        HoneySens.data.models.events.reset();
                        delete HoneySens.data.models.events.queryParams.filter;  // don't leak search strings
                        HoneySens.data.models.new_events.reset();
                        HoneySens.data.models.eventfilters.fullCollection.reset();
                        HoneySens.data.models.sensors.fullCollection.reset();
                        HoneySens.data.models.users.reset();
                        HoneySens.data.models.divisions.reset();
                        HoneySens.data.models.contacts.reset();
                        HoneySens.data.models.services.reset();
                        HoneySens.data.models.platforms.reset();
                        HoneySens.data.models.tasks.reset();
                        HoneySens.data.models.logs.reset();
                        HoneySens.data.settings.clear();
                        HoneySens.data.lastEventID = null;
                        HoneySens.data.lastUpdateTimestamp = null;
                        HoneySens.vent.trigger('logout:success');
                    },
                    error: function() {
                        location.reload();
                    }
                });
            });
            HoneySens.reqres.setHandler('login', function(credentials) {
                $.ajax({
                    type: 'POST',
                    url: 'api/sessions',
                    data: JSON.stringify(credentials),
                    success: function(data) {
                        HoneySens.vent.trigger('login:success');
                        data = JSON.parse(data);
                        var user = new Models.User(data);
                        HoneySens.data.session.user = user;
                        if(user.get('require_password_change')) {
                            document.location.hash = '#setup/changepw';
                        } else {
                            // Retrieve application state
                            $.ajax({
                                type: 'GET',
                                url: 'api/state',
                                success: function (data) {
                                    data = JSON.parse(data);
                                    // Update client model
                                    HoneySens.data.models.sensors.fullCollection.reset(data.sensors);
                                    HoneySens.data.models.eventfilters.fullCollection.reset(data.event_filters);
                                    HoneySens.data.models.users.reset(data.users);
                                    HoneySens.data.models.divisions.reset(data.divisions);
                                    HoneySens.data.models.contacts.reset(data.contacts);
                                    HoneySens.data.models.services.reset(data.services);
                                    HoneySens.data.models.platforms.reset(data.platforms);
                                    HoneySens.data.models.stats.set(data.stats);
                                    HoneySens.data.models.tasks.reset(data.tasks);
                                    HoneySens.data.models.logs.reset();
                                    HoneySens.data.settings.set(data.settings);
                                    HoneySens.data.system.set(data.system);
                                    HoneySens.data.lastEventID = data.lastEventID;
                                    HoneySens.data.lastUpdateTimestamp = data.timestamp;
                                    // Update Layout
                                    HoneySens.commands.execute('init:layout');
                                    // Navigate to dashboard
                                    HoneySens.router.navigate('login');
                                    HoneySens.router.navigate('', {trigger: true});
                                    HoneySens.commands.execute('counter:start');
                                }
                            });
                        }
                    },
                    error: function() {
                        HoneySens.vent.trigger('login:failed');
                    }
                });
            });

            // Initialize events
            HoneySens.vent.on('logout:success', function(user) {
                HoneySens.stopCurrentModule();
                HoneySens.request('view:content-region').show(new LoginView());
                HoneySens.request('view:navigation').empty();
                if(HoneySens.router) HoneySens.router.navigate(''); // only clear URL if router is initialized already
            });

            HoneySens.commands.setHandler('init:layout', function() {
                HoneySens.request('view:content-region').show(new AppLayoutView());
                HoneySens.request('view:navigation').show(new NavigationView({model: HoneySens.data.session.user}));
                HoneySens.request('view:content').sidebar.show(new SidebarView());
            });

            HoneySens.commands.setHandler('init:finalize', function() {
                // Inteitialize Layout according to system and session status
                var user = HoneySens.data.session.user;
                if(HoneySens.data.system.get('setup')) {
                    document.location.hash = '#setup';
                } else if(user.get('require_password_change')) {
                    document.location.hash = '#setup/changepw';
                } else if(user.get('role') > Models.User.role.GUEST) {
                    if (HoneySens.data.system.get('update')) {
                        document.location.hash = '#setup';
                    } else {
                        HoneySens.commands.execute('init:layout');
                        HoneySens.commands.execute('counter:start');
                    }
                } else {
                    HoneySens.vent.trigger('logout:success');
                }
                // Initialize main router
                HoneySens.router = new Controller.Router({
                    controller: Controller
                });
                Backbone.history.start();
            });

            HoneySens.commands.setHandler('fetchUpdates', function() {
                let stats = HoneySens.data.models.stats,
                    url = 'api/state?ts=' + HoneySens.data.lastUpdateTimestamp + '&last_id=' + HoneySens.data.lastEventID + '&stats_year=' + stats.get('year') + '&stats_month='+ stats.get('month') + '&stats_division=' + stats.get('division');
                $.ajax({
                    type: 'GET',
                    url: url,
                    success: function(data) {
                        data = JSON.parse(data);
                        HoneySens.data.lastUpdateTimestamp = data.timestamp;
                        HoneySens.data.lastEventID = data.lastEventID;
                        if(data.new_events.length > 0) {
                            HoneySens.data.models.new_events.add(data.new_events);
                            HoneySens.vent.trigger('models:events:new', _.pluck(data.new_events.items, 'id'));
                        }
                        if(_.has(data, 'event_filters')) HoneySens.data.models.eventfilters.fullCollection.reset(data.event_filters);
                        if(_.has(data, 'sensors')) HoneySens.data.models.sensors.fullCollection.reset(data.sensors);
                        if(_.has(data, 'users')) HoneySens.data.models.users.set(data.users);
                        if(_.has(data, 'divisions')) HoneySens.data.models.divisions.set(data.divisions);
                        if(_.has(data, 'settings')) HoneySens.data.settings.set(data.settings);
                        if(_.has(data, 'system')) HoneySens.data.system.set(data.system);
                        if(_.has(data, 'contacts')) HoneySens.data.models.contacts.set(data.contacts);
                        if(_.has(data, 'services')) HoneySens.data.models.services.set(data.services);
                        if(_.has(data, 'platforms')) HoneySens.data.models.platforms.set(data.platforms);
                        if(_.has(data, 'stats')) HoneySens.data.models.stats.set(data.stats);
                        if(_.has(data, 'tasks')) HoneySens.data.models.tasks.set(data.tasks);
                        HoneySens.vent.trigger('models:updated');
                        HoneySens.execute('counter:start');
                    },
                    error: function() {
                        HoneySens.request('view:modal').show(new ModalServerError({
                            model: new Backbone.Model({
                                msg: 'Verbindung zum Server verloren, Website wird nach Klick auf \'OK\' neu geladen.',
                                onClose: function() {
                                    window.location.reload();
                                }
                            })
                        }));
                    }
                });
            });

            HoneySens.commands.setHandler('counter:start', function() {
                var counter = 10,
                    stopCounter = function() {
                        HoneySens.vent.off('logout:success', stopCounter);
                        clearInterval(eventCounter);
                    },
                    eventCounter = setInterval(function() {
                        counter--;
                        HoneySens.vent.trigger('counter:updated', counter);
                        if(counter <= 0) {
                            stopCounter();
                            HoneySens.execute('fetchUpdates');
                        }
                    }, 1000);
                HoneySens.vent.trigger('counter:started');
                HoneySens.vent.on('logout:success', stopCounter);
            });


            // Frontend initialization entry point
            $.ajax({
                type: 'GET',
                url: 'api/state',
                success: function(data) {
                    data = JSON.parse(data);
                    HoneySens.data.session.user.set(data.user);
                    HoneySens.data.models.sensors.fullCollection.reset(data.sensors);
                    HoneySens.data.models.eventfilters.fullCollection.reset(data.event_filters);
                    HoneySens.data.models.users.reset(data.users);
                    HoneySens.data.models.divisions.reset(data.divisions);
                    HoneySens.data.models.contacts.reset(data.contacts);
                    HoneySens.data.models.services.reset(data.services);
                    HoneySens.data.models.platforms.reset(data.platforms);
                    HoneySens.data.models.stats.set(data.stats);
                    HoneySens.data.models.tasks.reset(data.tasks);
                    HoneySens.data.settings.set(data.settings);
                    HoneySens.data.system.set(data.system);
                    HoneySens.data.lastEventID = data.lastEventID;
                    HoneySens.data.lastUpdateTimestamp = data.timestamp;
                    HoneySens.commands.execute('init:finalize');
                }
            });
        });
    });

    return HoneySens.Controller;
});
