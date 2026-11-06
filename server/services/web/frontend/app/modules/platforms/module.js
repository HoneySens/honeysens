import i18n from 'app/common/i18n';
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
        {title: i18n.t("platforms:header"), uri: 'sensors/platforms', iconClass: 'glyphicon glyphicon-import', permission: {domain: 'sensors', action: 'get'}}
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
            radio.trigger('platforms:shown');
        });
        radio.reply('platforms:edit', function(platform) {
            radio.request('view:content').getRegion('overlay').showOverlay(
                new PlatformDetailsView({model: platform}),
                'sensors/platforms/' + platform.id,
                'sensors/platforms',
                router);
        });
        radio.reply('platforms:firmware:add', function() {
            radio.request('view:content').getRegion('overlay').showOverlay(
                new FileUploadView(),
                'sensors/platforms/add',
                'sensors/platforms',
                router);
        });
        radio.reply('platforms:firmware:remove', function(platform) {
            radio.request('view:modal').show(new ModalFirmwareRemoveView({model: platform}));
        });
    },
    stop: function() {
        console.log('Stopping module: platforms');
        radio.stopReplying('platforms:show');
        radio.stopReplying('platforms:edit');
    },
    routesList: {
        'sensors/platforms': 'showPlatforms',
        'sensors/platforms/:id': 'editPlatform',
        'sensors/platforms/add': 'addPlatformFirmware'
    },
    showPlatforms: function() {
        radio.request('platforms:show');
        this.router.navigate('sensors/platforms');
    },
    addPlatformFirmware: function() {
        radio.request('platforms:show');
        radio.request('platforms:firmware:add');
    },
    editPlatform: function(id) {
        var platform = HoneySens.data.models.platforms.get(id);
        if(platform) {
            radio.request('platforms:show');
            radio.request('platforms:edit', platform);
        } else this.showPlatforms();
    }
});

export default PlatformsModule;