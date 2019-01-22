define(['app/app', 'app/models'], function(HoneySens, Models) {
    var routingModule = Marionette.Module.extend({
        initialize: function() {
            // Register menu structure if the module provides one
            if(this.menuItems) HoneySens.addMenuItems(this.menuItems);

            // Routing
            if(!this.routesList) return;
            var Router = Backbone.Router.extend({
                current: function() {
                    var Router = this,
                        fragment = Backbone.history.fragment,
                        routes = _.pairs(Router.routes),
                        route = null, params = null, matched;

                    matched = _.find(routes, function(handler) {
                        route = _.isRegExp(handler[0]) ? handler[0] : Router._routeToRegExp(handler[0]);
                        return route.test(fragment);
                    });

                    if(matched) {
                        params = Router._extractParameters(route, fragment);
                        route = matched[1];
                    }

                    return {
                        route : route,
                        fragment : fragment,
                        params : params
                    };
                }
            });
            this.router = new Router();

            for(var route in this.routesList) {
                if(!this.routesList.hasOwnProperty(route)) continue;
                var routeName = this.routesList[route];
                this.router.route(route, routeName, $.proxy(function() {
                    // Not logged-in users are only permitted to access the setup page (for the initial setup)
                    if(HoneySens.data.session.user.get('role') == Models.User.role.GUEST && this.module.name != 'setup') return;
                    // Prevent the module start of any other module if the setup is running
                    if((HoneySens.data.system.get('setup') || HoneySens.data.system.get('update')) && this.module.name != 'setup') return;
                    HoneySens.startModule(this.module);
                    $.isFunction(this.module[this.routeName]) && this.module[this.routeName].apply(this.module, arguments);
                }, { module: this, routeName: routeName}));
            }
        }
    });

    return routingModule;
});