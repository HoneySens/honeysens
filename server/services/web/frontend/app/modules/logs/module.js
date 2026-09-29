import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/logs/views/Layout';
import LogListView from 'app/modules/logs/views/LogList';

var LogsModule = createRoutingModule({
    name: 'logs',
    startWithParent: false,
    rootView: null,
    menuItems: [{
        title: i18n.t('logs:header'),
        uri: 'logs',
        iconClass: 'glyphicon glyphicon-book',
        permission: {domain: 'logs', action: 'get'},
        priority: 5,
    }],
    start: function() {
        console.log('Starting module: logs');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('logs:show', function() {
            if(!HoneySens.assureAllowed('logs', 'get')) return false;
            var logs = HoneySens.data.models.logs;
            contentRegion.show(new LogListView({collection: logs}));
            router.navigate('logs');
            radio.trigger('logs:shown');
        });
    },
    stop: function() {
        console.log('Stopping module: logs');
        radio.stopReplying('logs:show');
    },
    routesList: {
        'logs': 'showLogs'
    },
    showLogs: function() {radio.request('logs:show');}
});

export default LogsModule;