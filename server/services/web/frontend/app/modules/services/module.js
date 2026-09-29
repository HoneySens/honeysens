import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/services/views/Layout';
import ServiceListView from 'app/modules/services/views/ServiceList';
import FileUploadView from 'app/common/views/FileUpload';
import ServiceDetailsView from 'app/modules/services/views/ServiceDetails';
import ModalServiceRemoveView from 'app/modules/services/views/ModalServiceRemove';
import ModalServiceRevisionRemoveView from 'app/modules/services/views/ModalServiceRevisionRemove';

var ServicesModule = createRoutingModule({
    name: 'services',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: i18n.t('services:header'), uri: 'sensors/services', iconClass: 'glyphicon glyphicon-asterisk', permission: {domain: 'sensors', action: 'get'}}
    ],
    start: function() {
        console.log('Starting module: services');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('services:show', function() {
            if(!HoneySens.assureAllowed('services', 'get')) return false;
            contentRegion.show(new ServiceListView({collection: HoneySens.data.models.services}));
            router.navigate('sensors/services');
            radio.trigger('services:shown');
        });
        radio.reply('services:add', function() {
            radio.request('view:content').getRegion('overlay').show(new FileUploadView());
        });
        radio.reply('services:remove', function(model) {
            radio.request('view:modal').show(new ModalServiceRemoveView({model: model}));
        });
        radio.reply('services:details', function(model) {
            radio.request('view:content').getRegion('overlay').show(new ServiceDetailsView({model: model}));
        });
        radio.reply('services:revisions:remove', function(model) {
            radio.request('view:modal').show(new ModalServiceRevisionRemoveView({model: model}));
        });
    },
    stop: function() {
        console.log('Stopping module: services');
        radio.stopReplying('services:show');
        radio.stopReplying('services:add');
        radio.stopReplying('services:remove');
        radio.stopReplying('services:details');
    },
    routesList: {
        'sensors/services': 'showServices',
        'sensors/services/add': 'addService'
    },
    showServices: function() {radio.request('services:show');},
    addService: function() {radio.request('services:add');}
});

export default ServicesModule;