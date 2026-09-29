import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/info/views/Layout';
import OverView from 'app/modules/info/views/Overview';

var InfoModule = createRoutingModule({
    name: 'info',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: i18n.t('info:infoHeader'), uri: 'info', iconClass: 'glyphicon glyphicon-info-sign', permission: {domain: 'state', action: 'get'}}
    ],
    start: function() {
        console.log('Starting module: info');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('info:show', function() {
            contentRegion.show(new OverView());
            router.navigate('info');
            radio.trigger('info:shown');
        });
    },
    stop: function() {
        console.log('Stopping module: info');
        radio.stopReplying('info:show');
    },
    routesList: {
        'info': 'showInfo'
    },
    showInfo: function() {radio.request('info:show');},
});

export default InfoModule;
