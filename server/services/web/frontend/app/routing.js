import HoneySens from 'app/app';
import { UserRole } from 'app/models';
import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

// Factory that turns a plain module definition into a routable module.
// Registers the menu items, if any are defined, then builds a `Backbone.Router`
// whose routes start the module and dispatch to the matching action.
export function createRoutingModule(module) {
    // Register menu structure if the module provides one
    if (module.menuItems) HoneySens.addMenuItems(module.menuItems);

    // Routing
    if (!module.routesList) return module;

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

            if (matched) {
                params = Router._extractParameters(route, fragment);
                route = matched[1];
            }

            return {
                route: route,
                fragment: fragment,
                params: params
            };
        }
    });
    module.router = new Router();

    for (const [route, routeName] of Object.entries(module.routesList)) {
        module.router.route(route, routeName, function() {
            // Not logged-in users are only permitted to access the setup page (for the initial setup)
            if (HoneySens.data.session.user.get('role') === UserRole.GUEST && module.name !== 'setup') return;
            // Prevent the module start of any other module if the setup is running
            if ((HoneySens.data.system.get('setup') || HoneySens.data.system.get('update')) && module.name !== 'setup') return;
            HoneySens.startModule(module);
            // Check if the module actually has an action for this route
            if ($.isFunction(module[routeName])) {
                module[routeName].apply(module, arguments);
            }
        });
    }

    return module;
}

export default createRoutingModule;
