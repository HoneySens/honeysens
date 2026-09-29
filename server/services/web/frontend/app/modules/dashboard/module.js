import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import { Stats } from 'app/models';
import LayoutView from 'app/modules/dashboard/views/Layout';
import Dashboardview from 'app/modules/dashboard/views/Dashboard';

var DashboardModule = createRoutingModule({
    name: 'dashboard',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: i18n.t('dashboard:header'), uri: '', iconClass: 'glyphicon glyphicon-globe', permission: {domain: 'events', action: 'get'}, priority: 0}
    ],
    start: function() {
        console.log('Starting module: dashboard');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('dashboard:show', function() {
            if(!HoneySens.assureAllowed('events', 'get')) return false;
            contentRegion.show(new Dashboardview({model: new Stats()}));
            router.navigate('');
            radio.trigger('dashboard:shown');
        });
    },
    stop: function() {
        console.log('Stopping module: dashboard');
        radio.stopReplying('dashboard:show');
    },
    routesList: {
        '': 'showDashboard'
    },
    showDashboard: function() {radio.request('dashboard:show');}
});

export default DashboardModule;