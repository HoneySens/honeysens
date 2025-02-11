import HoneySens from 'app/app';
import Routing from 'app/routing';
import Models from 'app/models';
import LayoutView from 'app/modules/dashboard/views/Layout';
import SummaryView from 'app/modules/dashboard/views/Summary';

var DashboardModule = Routing.extend({
    name: 'dashboard',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: _.t('dashboard:header'), uri: '', iconClass: 'glyphicon glyphicon-globe', permission: {domain: 'events', action: 'get'}, priority: 0}
    ],
    start: function() {
        console.log('Starting module: dashboard');
        this.rootView = new LayoutView();
        HoneySens.request('view:content').main.show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        HoneySens.reqres.setHandler('dashboard:show', function() {
            if(!HoneySens.assureAllowed('events', 'get')) return false;
            contentRegion.show(new SummaryView({model: new Models.Stats()}));
            router.navigate('');
            HoneySens.vent.trigger('dashboard:shown');
        });
    },
    stop: function() {
        console.log('Stopping module: dashboard');
        HoneySens.reqres.removeHandler('dashboard:show');
    },
    routesList: {
        '': 'showDashboard'
    },
    showDashboard: function() {HoneySens.request('dashboard:show');}
});

export default HoneySens.module('Dashboard.Routing', DashboardModule);