define(['app/app',
        'app/models',
		'app/views/AppLayout',
        'app/views/Login',
        'app/views/Navigation',
        'app/views/Sidebar',
        'json',
        'app/modules/setup/module'],
function(HoneySens, Models, AppLayoutView, LoginView, NavigationView, SidebarView, JSON) {
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
				if(HoneySens.data.session.user.get('role') > Models.User.role.GUEST) {
					$.ajax({
						type: 'DELETE',
						url: 'api/sessions',
						success: function(data) {
							data = JSON.parse(data);
							HoneySens.data.session.user = new Models.User(data.user);
							// clear models
							HoneySens.data.models.certs.reset();
							HoneySens.data.models.events.reset();
                            HoneySens.data.models.eventfilters.fullCollection.reset();
							HoneySens.data.models.sensors.fullCollection.reset();
							HoneySens.data.models.users.reset();
							HoneySens.data.models.divisions.reset();
							HoneySens.data.models.contacts.reset();
							HoneySens.data.models.services.reset();
							HoneySens.data.models.platforms.reset();
							HoneySens.data.settings.clear();
                            HoneySens.vent.trigger('logout:success');
						}
					});
				}
			});
            HoneySens.reqres.setHandler('login', function(credentials) {
                $.ajax({
                    type: 'POST',
                    url: 'api/sessions',
                    data: JSON.stringify(credentials),
                    success: function(data) {
                        HoneySens.vent.trigger('login:success');
                        data = JSON.parse(data);
                        HoneySens.data.session.user = new Models.User(data);
                        // Retrieve application state
                        $.ajax({
                            type: 'GET',
                            url: 'api/state',
                            success: function(data) {
                                data = JSON.parse(data);
                                // Update client model
                                HoneySens.data.models.sensors.fullCollection.reset(data.sensors);
                                var totalRecords = parseInt(data.events.total_count) || 0;
                                HoneySens.data.models.events.state.totalRecords = totalRecords;
                                HoneySens.data.models.events.reset(data.events.items);
                                HoneySens.data.models.eventfilters.fullCollection.reset(data.event_filters);
                                HoneySens.data.models.users.reset(data.users);
                                HoneySens.data.models.divisions.reset(data.divisions);
                                HoneySens.data.models.contacts.reset(data.contacts);
                                HoneySens.data.models.services.reset(data.services);
                                HoneySens.data.models.platforms.reset(data.platforms);
                                HoneySens.data.models.stats.set(data.stats);
                                HoneySens.data.settings.set(data.settings);
                                HoneySens.data.system.set(data.system);
                                HoneySens.data.lastUpdateTimestamp = data.timestamp;
                                // Redirect to setup if an update is imminent
                                if(HoneySens.data.system.get('update')) {
                                    HoneySens.router.navigate('setup', {trigger: true});
                                } else {
                                    // Update Layout
                                    HoneySens.commands.execute('init:layout');
                                    // Navigate to dashboard
                                    HoneySens.router.navigate('login');
                                    HoneySens.router.navigate('', {trigger: true});
                                    HoneySens.commands.execute('counter:start');
                                }
                            }
                        });
                    },
                    error: function() {
                        HoneySens.vent.trigger('login:failed');
                    }
                });
            });

			// Initialize events
			HoneySens.vent.on('logout:success', function(user) {
                HoneySens.request('view:content-region').show(new LoginView());
                if(HoneySens.router) HoneySens.router.navigate(''); // only clear URL if router is initialized already
			});

            HoneySens.commands.setHandler('init:layout', function() {
                HoneySens.request('view:content-region').show(new AppLayoutView());
                HoneySens.request('view:content').navigation.show(new NavigationView({model: HoneySens.data.session.user}));
                HoneySens.request('view:content').sidebar.show(new SidebarView());
            });

            HoneySens.commands.setHandler('init:finalize', function() {
                // Initialize Layout according to system and session status
                if(HoneySens.data.system.get('setup')) {
                    document.location.hash = '#setup';
                } else if(HoneySens.data.session.user.get('role') > Models.User.role.GUEST) {
                    if(HoneySens.data.system.get('update')) {
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

            // Initialize state
            $.ajax({
                type: 'GET',
                url: 'api/state',
                success: function(data) {
                    data = JSON.parse(data);
                    HoneySens.data.session.user.set(data.user);
                    HoneySens.data.models.sensors.fullCollection.reset(data.sensors);
                    var totalRecords = parseInt(data.events.total_count) || 0;
                    HoneySens.data.models.events.state.totalRecords = totalRecords;
                    HoneySens.data.models.events.reset(data.events.items);
                    HoneySens.data.models.eventfilters.fullCollection.reset(data.event_filters);
                    HoneySens.data.models.users.reset(data.users);
                    HoneySens.data.models.divisions.reset(data.divisions);
                    HoneySens.data.models.contacts.reset(data.contacts);
                    HoneySens.data.models.services.reset(data.services);
                    HoneySens.data.models.platforms.reset(data.platforms);
                    HoneySens.data.models.stats.set(data.stats);
                    HoneySens.data.settings.set(data.settings);
                    HoneySens.data.system.set(data.system);
                    HoneySens.data.lastUpdateTimestamp = data.timestamp;
                    HoneySens.commands.execute('init:finalize');
                }
            });
		});
	});

	return HoneySens.Controller;
});