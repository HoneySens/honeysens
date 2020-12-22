define(['app/app', 'app/models', 'app/routing',
        'app/modules/logs/views/Layout',
        'app/modules/logs/views/LogList'],
function(HoneySens, Models, Routing, Layoutview, LogListView) {
    var LogsModule = Routing.extend({
        name: 'logs',
        startWithParent: false,
        rootView: null,
        menuItems: [{
            title: 'Logs',
            uri: 'logs',
            iconClass: 'glyphicon glyphicon-book',
            permission: {domain: 'logs', action: 'get'},
            priority: 5,
        }],
        start: function() {
            console.log('Starting module: logs');
            this.rootView = new Layoutview();
            HoneySens.request('view:content').main.show(this.rootView);

            // Register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('logs:show', function() {
                if(!HoneySens.assureAllowed('logs', 'get')) return false;
                var logs = HoneySens.data.models.logs;
                contentRegion.show(new LogListView({collection: logs}));
                router.navigate('logs');
                HoneySens.vent.trigger('logs:shown');
            });
        },
        stop: function() {
            console.log('Stopping module: logs');
            HoneySens.reqres.removeHandler('logs:show');
        },
        routesList: {
            'logs': 'showLogs'
        },
        showLogs: function() {HoneySens.request('logs:show');}
    });

    return HoneySens.module('Logs.Routing', LogsModule);
});