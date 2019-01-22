define(['app/app',
        'app/routing',
        'app/modules/info/views/Layout',
        'app/modules/info/views/Overview'],
function(HoneySens, Routing, LayoutView, OverView) {
    var InfoModule = Routing.extend({
        name: 'info',
        startWithParent: false,
        rootView: null,
        menuItems: [
            {title: 'Information', uri: 'info', iconClass: 'glyphicon glyphicon-info-sign', permission: {domain: 'state', action: 'get'}}
        ],
        start: function() {
            console.log('Starting module: info');
            this.rootView = new LayoutView();
            HoneySens.request('view:content').main.show(this.rootView);

            // Register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('info:show', function() {
                contentRegion.show(new OverView());
                router.navigate('info');
                HoneySens.vent.trigger('info:shown');
            });
        },
        stop: function() {
            console.log('Stopping module: info');
            HoneySens.reqres.removeHandler('info:show');
        },
        routesList: {
            'info': 'showInfo'
        },
        showInfo: function() {HoneySens.request('info:show');}
    });

    return HoneySens.module('Info.Routing', InfoModule);
});