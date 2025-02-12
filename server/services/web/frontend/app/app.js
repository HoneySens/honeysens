import RootLayout from 'app/views/RootLayout';
import Marionette from 'backbone.marionette';
import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';
import i18n from 'app/common/i18n';

var app = new Marionette.Application();

// Disable AJAX request caching (fixes some bugs with Internet Explorer)
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

// i18n support injected into underscore
_.t = i18n.t;
var selectedLang = localStorage.getItem('lang');
if(selectedLang !== null) await i18n.setLanguage(selectedLang);
else await i18n.setLanguage(null);

// Global template helpers via an underscore property
_.templateHelpers = {
    isAllowed: function(domain, action) {
        var permissions = app.data.session.user.get('permissions');
        return domain in permissions && ($.inArray(action, permissions[domain]) > -1)
    },
    getModels: function() {
        return app.Models;
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

export default app;