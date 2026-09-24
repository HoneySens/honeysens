import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/platforms/views/Layout';
import PlatformListView from 'app/modules/platforms/views/PlatformList';
import PlatformDetailsView from 'app/modules/platforms/views/PlatformDetails';
import FileUploadView from 'app/common/views/FileUpload';
import ModalFirmwareRemoveView from 'app/modules/platforms/views/ModalFirmwareRemove';

var PlatformsModule = createRoutingModule({
    name: 'platforms',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: _.t("platforms:header"), uri: 'sensors/platforms', iconClass: 'glyphicon glyphicon-import', permission: {domain: 'sensors', action: 'get'}}
    ],
    start: function() {
        console.log('Starting module: platforms');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('platforms:show', function() {
            if(!HoneySens.assureAllowed('sensors', 'get')) return false;
            contentRegion.show(new PlatformListView({collection: HoneySens.data.models.platforms}));
            router.navigate('sensors/platforms');
            radio.trigger('platforms:shown');
        });
        radio.reply('platforms:details', function(model) {
            radio.request('view:content').getRegion('overlay').show(new PlatformDetailsView({model: model}));
        });
        radio.reply('platforms:firmware:add', function() {
            radio.request('view:content').getRegion('overlay').show(new FileUploadView());
        });
        radio.reply('platforms:firmware:remove', function(model) {
            radio.request('view:modal').show(new ModalFirmwareRemoveView({model: model}));
        });
    },
    stop: function() {
        console.log('Stopping module: platforms');
        radio.stopReplying('platforms:show');
        radio.stopReplying('platforms:details');
    },
    routesList: {
        'sensors/platforms': 'showPlatforms'
    },
    showPlatforms: function() {radio.request('platforms:show');}
});

export default HoneySens.module('Platforms.Routing', PlatformsModule);