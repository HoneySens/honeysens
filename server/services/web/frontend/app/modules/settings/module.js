import HoneySens from 'app/app';
import Routing from 'app/routing';
import LayoutView from 'app/modules/settings/views/Layout';
import Overview from 'app/modules/settings/views/Overview';

var SettingsModule = Routing.extend({
    name: 'settings',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: _.t('settings:header'), uri: 'settings', iconClass: 'glyphicon glyphicon-cog', permission: {domain: 'settings', action: 'update'}, priority: 3}
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

export default HoneySens.module('Settings.Routing', SettingsModule);