import { radio } from 'app/radio';
import HoneySens from 'app/app';
import Models from 'app/models';
import AppLayoutView from 'app/views/AppLayout';
import LoginView from 'app/views/Login';
import NavigationView from 'app/views/Navigation';
import SidebarView from 'app/views/Sidebar';
import ModalServerError from 'app/common/views/ModalServerError';
import 'app/modules/setup/module';

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
        radio.request('logout');
    };

    Controller.Router = Backbone.Router.extend({
        routes: {
            'logout': function() {
                Controller.doLogout();
            }
        }
    });

    HoneySens.addInitializer(function() {
        // Session management commands
        radio.reply('logout', function() {
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
                    radio.trigger('logout:success');
                },
                error: function() {
                    location.reload();
                }
            });
        });
        radio.reply('login', function(credentials) {
            $.ajax({
                type: 'POST',
                url: 'api/sessions',
                data: JSON.stringify(credentials),
                contentType: 'application/json',
                success: function(data) {
                    radio.trigger('login:success');
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
                                HoneySens.data.models.tasks.reset(data.tasks);
                                HoneySens.data.models.logs.reset();
                                HoneySens.data.settings.set(data.settings);
                                HoneySens.data.system.set(data.system);
                                HoneySens.data.lastEventID = data.lastEventID;
                                HoneySens.data.lastUpdateTimestamp = data.timestamp;
                                // Update Layout
                                radio.request('init:layout');
                                // Navigate to dashboard
                                HoneySens.router.navigate('login');
                                HoneySens.router.navigate('', {trigger: true});
                                radio.request('counter:start');
                            }
                        });
                    }
                },
                error: function() {
                    radio.trigger('login:failed');
                }
            });
        });

        // Initialize events
        radio.on('logout:success', function(user) {
            HoneySens.stopCurrentModule();
            radio.request('view:content-region').show(new LoginView());
            radio.request('view:navigation').empty();
            if(HoneySens.router) HoneySens.router.navigate(''); // only clear URL if router is initialized already
        });

        radio.reply('init:layout', function() {
            radio.request('view:content-region').show(new AppLayoutView());
            radio.request('view:navigation').show(new NavigationView({model: HoneySens.data.session.user}));
            radio.request('view:content').getRegion('sidebar').show(new SidebarView());
        });

        radio.reply('init:finalize', function() {
            // Initialize Layout according to system and session status
            var user = HoneySens.data.session.user;
            if(HoneySens.data.system.get('setup')) {
                document.location.hash = '#setup';
            } else if(user.get('require_password_change')) {
                document.location.hash = '#setup/changepw';
            } else if(user.get('role') > Models.User.role.GUEST) {
                if (HoneySens.data.system.get('update')) {
                    document.location.hash = '#setup';
                } else {
                    radio.request('init:layout');
                    radio.request('counter:start');
                }
            } else {
                radio.trigger('logout:success');
            }
            // Initialize main router
            HoneySens.router = new Controller.Router({
                controller: Controller
            });
            Backbone.history.start();
        });

        radio.reply('fetchUpdates', function(startNewCounter) {
            let url = 'api/state?ts=' + HoneySens.data.lastUpdateTimestamp + '&last_id=' + HoneySens.data.lastEventID;
            $.ajax({
                type: 'GET',
                url: url,
                success: function(data) {
                    data = JSON.parse(data);
                    HoneySens.data.lastUpdateTimestamp = data.timestamp;
                    HoneySens.data.lastEventID = data.lastEventID;
                    if(data.new_events.length > 0) {
                        HoneySens.data.models.new_events.add(data.new_events);
                        radio.trigger('models:events:new', _.pluck(data.new_events.items, 'id'));
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
                    if(_.has(data, 'tasks')) HoneySens.data.models.tasks.set(data.tasks);
                    radio.trigger('models:updated');
                    if(startNewCounter) radio.request('counter:start');
                },
                error: function() {
                    radio.request('view:modal').show(new ModalServerError({
                        model: new Backbone.Model({
                            msg: _.t('layout:connectionLost'),
                            onClose: function() {
                                window.location.reload();
                            }
                        })
                    }));
                }
            });
        });

        radio.reply('counter:start', function() {
            var counter = 10,
                stopCounter = function() {
                    radio.off('logout:success', stopCounter);
                    clearInterval(eventCounter);
                },
                eventCounter = setInterval(function() {
                    counter--;
                    radio.trigger('counter:updated', counter);
                    if(counter <= 0) {
                        stopCounter();
                        radio.request('fetchUpdates', true);
                    }
                }, 1000);
            radio.trigger('counter:started');
            radio.on('logout:success', stopCounter);
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
                HoneySens.data.models.tasks.reset(data.tasks);
                HoneySens.data.settings.set(data.settings);
                HoneySens.data.system.set(data.system);
                HoneySens.data.lastEventID = data.lastEventID;
                HoneySens.data.lastUpdateTimestamp = data.timestamp;
                radio.request('init:finalize');
            },
            error: function(data) {
                // Receiving an HTTP 403 from api/state indicates a session timeout.
                // The session will now be discarded on the server, we can safely reload the page.
                if(data.status === 403) location.reload();
            }
        });
    });
});

export default HoneySens.Controller;