import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/settings/views/Layout';
import Overview from 'app/modules/settings/views/Overview';

var SettingsModule = createRoutingModule({
    name: 'settings',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: i18n.t('settings:header'), uri: 'settings', iconClass: 'glyphicon glyphicon-cog', permission: {domain: 'settings', action: 'update'}, priority: 3}
    ],
    start: function() {
        console.log('Starting module: settings');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('settings:show', function() {
            if(!HoneySens.assureAllowed('settings', 'get')) return false;
            contentRegion.show(new Overview({model: HoneySens.data.settings}));
            router.navigate('settings');
        });
    },
    stop: function() {
        console.log('Stopping module: settings');
        radio.stopReplying('settings:show');
    },
    routesList: {
        'settings': 'showSettings'
    },
    showSettings: function() {radio.request('settings:show');}
});

export default SettingsModule;