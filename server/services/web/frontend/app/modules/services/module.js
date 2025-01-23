import HoneySens from 'app/app';
import Routing from 'app/routing';
import LayoutView from 'app/modules/services/views/Layout';
import ServiceListView from 'app/modules/services/views/ServiceList';
import FileUploadView from 'app/common/views/FileUpload';
import ServiceDetailsView from 'app/modules/services/views/ServiceDetails';
import ModalServiceRemoveView from 'app/modules/services/views/ModalServiceRemove';
import ModalServiceRevisionRemoveView from 'app/modules/services/views/ModalServiceRevisionRemove';

var ServicesModule = Routing.extend({
    name: 'services',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: 'Dienste', uri: 'sensors/services', iconClass: 'glyphicon glyphicon-asterisk', permission: {domain: 'sensors', action: 'get'}}
    ],
    start: function() {
        console.log('Starting module: services');
        this.rootView = new LayoutView();
        HoneySens.request('view:content').main.show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        HoneySens.reqres.setHandler('services:show', function() {
            if(!HoneySens.assureAllowed('services', 'get')) return false;
            contentRegion.show(new ServiceListView({collection: HoneySens.data.models.services}));
            router.navigate('sensors/services');
            HoneySens.vent.trigger('services:shown');
        });
        HoneySens.reqres.setHandler('services:add', function() {
            HoneySens.request('view:content').overlay.show(new FileUploadView());
        });
        HoneySens.reqres.setHandler('services:remove', function(model) {
            HoneySens.request('view:modal').show(new ModalServiceRemoveView({model: model}));
        });
        HoneySens.reqres.setHandler('services:details', function(model) {
            HoneySens.request('view:content').overlay.show(new ServiceDetailsView({model: model}));
        });
        HoneySens.reqres.setHandler('services:revisions:remove', function(model) {
            HoneySens.request('view:modal').show(new ModalServiceRevisionRemoveView({model: model}));
        });
    },
    stop: function() {
        console.log('Stopping module: services');
        HoneySens.reqres.removeHandler('services:show');
        HoneySens.reqres.removeHandler('services:add');
        HoneySens.reqres.removeHandler('services:remove');
        HoneySens.reqres.removeHandler('services:details');
    },
    routesList: {
        'sensors/services': 'showServices',
        'sensors/services/add': 'addService'
    },
    showServices: function() {HoneySens.request('services:show');},
    addService: function() {HoneySens.request('services:add');}
});

export default HoneySens.module('Services.Routing', ServicesModule);