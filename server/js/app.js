define(['app/views/RootLayout',
        'marionette', 'backbone', 'jquery', 'json', 'underscore'],
function(RootLayout, Marionette, Backbone, $, JSON, _) {
    var app = new Marionette.Application();

    // Disabled AJAX request caching (fixes some bugs with Internet Explorer)
    $.ajaxSetup({cache: false});

    // Controls the lifecycle of submodules
    app.currentModule = null;
    app.startModule = function(module) {
        if(this.currentModule && this.currentModule === module) return;
        this.currentModule && this.currentModule.stop();
        this.currentModule = module;
        this.currentModule.start();
    };
    app.stopCurrentModule = function() {
        if(this.currentModule) {
            this.currentModule.stop();
            this.currentModule = null;
        }
    }

    /**
     * Global menu
     *
     * Top-level items are ordered according to their "priority" attribute, whereby a lower value results
     * in a higher position in the generated list.
     *
     * Expects items of the form {title: <title>, uri: <uri>, iconClass: <iconClass>, permission: <permission>}.
     */
    app.menuItems = [];
    app.addMenuItems = function(items) {
        $.each(items, function() {
            var parts = this.uri.split('/', 2);
            // 1st or 2nd level item?
            if(parts.length == 1) {
                // Overwrite existing item data if this is a 1st level item, otherwise just add it to the list
                if(_.contains(_.pluck(app.menuItems, 'uri'), parts[0])) {
                    var item = _.find(app.menuItems, function(i){return i.uri == parts[0]});
                    item.title = this.title;
                    item.iconClass = this.iconClass;
                    item.permission = this.permission;
                    item.priority = this.priority;
                } else {
                    this.items = [];
                    app.menuItems.push(this);
                }
            } else {
                // Add an empty 1st level category item if it doesn't exist yet
                var parent = _.find(app.menuItems, function(i){return i.uri == parts[0];});
                if(parent == undefined) {
                    parent = {uri: parts[0], items: []};
                    app.menuItems.push(parent);
                }
                parent.items.push(this);
            }
        });
        app.menuItems = _.sortBy(app.menuItems, 'priority');
    };

    // Check permissions for the current user regarding an action of a specific domain
    app.assureAllowed = function(domain, action) {
        return _.templateHelpers.isAllowed(domain, action);
    };

    // Global template helpers via an underscore property
    _.templateHelpers = {
        isAllowed: function(domain, action) {
            var permissions = require('app/app').data.session.user.get('permissions');
            return domain in permissions && ($.inArray(action, permissions[domain]) > -1)
        },
        getModels: function() {
            return require('app/models');
        }
    };

    app.addInitializer(function() {
        app.rootView = new RootLayout();
        app.rootView.render();

        // Modules can request the root views' regions
        app.reqres.setHandler('view:navigation', function() {
            return app.rootView.navigation;
        });
        app.reqres.setHandler('view:content-region', function() {
            return app.rootView.content;
        });
        app.reqres.setHandler('view:content', function() {
            return app.rootView.content.currentView;
        });
        app.reqres.setHandler('view:modal', function() {
            return app.rootView.modal;
        });

        app.commands.setHandler('fetchUpdates', function() {
            let stats = app.data.models.stats,
                url = 'api/state?ts=' + app.data.lastUpdateTimestamp + '&last_id=' + app.data.lastEventID + '&stats_year=' + stats.get('year') + '&stats_month='+ stats.get('month') + '&stats_division=' + stats.get('division');
            $.ajax({
                type: 'GET',
                url: url,
                success: function(data) {
                    data = JSON.parse(data);
                    app.data.lastUpdateTimestamp = data.timestamp;
                    app.data.lastEventID = data.lastEventID;
                    if(data.new_events.length > 0) {
                        app.data.models.new_events.add(data.new_events);
                        app.vent.trigger('models:events:new', _.pluck(data.new_events.items, 'id'));
                    }
                    if(_.has(data, 'event_filters')) app.data.models.eventfilters.fullCollection.reset(data.event_filters);
                    if(_.has(data, 'sensors')) app.data.models.sensors.fullCollection.reset(data.sensors);
                    if(_.has(data, 'users')) app.data.models.users.set(data.users);
                    if(_.has(data, 'divisions')) app.data.models.divisions.set(data.divisions);
                    if(_.has(data, 'settings')) app.data.settings.set(data.settings);
                    if(_.has(data, 'system')) app.data.system.set(data.system);
                    if(_.has(data, 'contacts')) app.data.models.contacts.set(data.contacts);
                    if(_.has(data, 'services')) app.data.models.services.set(data.services);
                    if(_.has(data, 'platforms')) app.data.models.platforms.set(data.platforms);
                    if(_.has(data, 'stats')) app.data.models.stats.set(data.stats);
                    if(_.has(data, 'tasks')) app.data.models.tasks.set(data.tasks);
                    app.vent.trigger('models:updated');
                }
            });
        });

        app.commands.setHandler('counter:start', function() {
            var counter = 10,
                stopCounter = function() {
                    app.vent.off('logout:success', stopCounter);
                    clearInterval(eventCounter);
                },
                eventCounter = setInterval(function() {
                    counter--;
                    app.vent.trigger('counter:updated', counter);
                    if(counter <= 0) {
                        app.execute('fetchUpdates');
                        clearInterval(eventCounter);
                        app.vent.off('logout:success', stopCounter);
                        app.execute('counter:start');
                    }
                }, 1000);
            app.vent.trigger('counter:started');
            app.vent.on('logout:success', stopCounter);
        });

        var settings = new Backbone.Model();
        settings.url = function() {return 'api/settings';};
        var system = new Backbone.Model();
        system.url = function() {return 'api/system';};

        app.data = {
            models: {},
            session: {
                user: null
            },
            lastUpdateTimestamp: 0,
            settings: settings,
            system: system
        };
    });

    return app;
});
