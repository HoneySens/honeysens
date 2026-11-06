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
            radio.trigger('services:shown');
        });
        radio.reply('services:add', function() {
            radio.request('view:content').getRegion('overlay').showOverlay(
                new FileUploadView(),
                'sensors/services/add',
                'sensors/services',
                router);
        });
        radio.reply('services:remove', function(service) {
            radio.request('view:modal').show(new ModalServiceRemoveView({model: service}));
        });
        radio.reply('services:edit', function(service) {
            radio.request('view:content').getRegion('overlay').showOverlay(
                new ServiceDetailsView({model: service}),
                'sensors/services/' + service.id,
                'sensors/services',
                router);
        });
        radio.reply('services:revisions:remove', function(service) {
            radio.request('view:modal').show(new ModalServiceRevisionRemoveView({model: service}));
        });
    },
    stop: function() {
        console.log('Stopping module: services');
        radio.stopReplying('services:show');
        radio.stopReplying('services:add');
        radio.stopReplying('services:remove');
        radio.stopReplying('services:edit');
    },
    routesList: {
        'sensors/services': 'showServices',
        'sensors/services/:id': 'editService',
        'sensors/services/add': 'addService'
    },
    showServices: function() {
        radio.request('services:show');
        this.router.navigate('sensors/services');
    },
    addService: function() {
        radio.request('services:show');
        radio.request('services:add');
    },
    editService: function(id) {
        var service = HoneySens.data.models.services.get(id);
        if(service) {
            radio.request('services:show');
            radio.request('services:edit', service);
        } else this.showServices();
    }
});

export default ServicesModule;