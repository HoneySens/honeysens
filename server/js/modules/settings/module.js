define(['app/app', 'app/routing',
        'app/modules/settings/views/Layout',
        'app/modules/settings/views/Overview'],
function(HoneySens, Routing, LayoutView, Overview) {
    var SettingsModule = Routing.extend({
        name: 'settings',
        startWithParent: false,
        rootView: null,
        menuItems: [
            {title: 'System', uri: 'settings', iconClass: 'glyphicon glyphicon-cog', permission: {domain: 'settings', action: 'update'}, priority: 3}
            /*{title: 'Protokolle', uri: 'settings/logs', iconClass: 'glyphicon glyphicon-log-in', permission: 'settings'}*/
        ],
        start: function() {
            console.log('Starting module: settings');
            this.rootView = new LayoutView();
            HoneySens.request('view:content').main.show(this.rootView);

            // register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('settings:show', function() {
                if(!HoneySens.assureAllowed('settings', 'get')) return false;
                contentRegion.show(new Overview({model: HoneySens.data.settings}));
                router.navigate('settings');
            });
        },
        stop: function() {
            console.log('Stopping module: settings');
            HoneySens.reqres.removeHandler('settings:show');
        },
        routesList: {
            'settings': 'showSettings'
        },
        showSettings: function() {HoneySens.request('settings:show');}
    });

    return HoneySens.module('Settings.Routing', SettingsModule);
});