define(['app/app',
        'app/routing',
        'app/modules/platforms/views/Layout',
        'app/modules/platforms/views/PlatformList',
        'app/modules/platforms/views/PlatformDetails',
        'app/modules/platforms/views/ModalFirmwareUpload',
        'app/modules/platforms/views/ModalFirmwareRemove'],
function(HoneySens, Routing, LayoutView, PlatformListView, PlatformDetailsView, ModalFirmwareUploadView, ModalFirmwareRemoveView) {
    var PlatformsModule = Routing.extend({
        name: 'platforms',
        startWithParent: false,
        rootView: null,
        menuItems: [
            {title: 'Plattformen', uri: 'sensors/platforms', iconClass: 'glyphicon glyphicon-import', permission: {domain: 'sensors', action: 'get'}}
        ],
        start: function() {
            console.log('Starting module: platforms');
            this.rootView = new LayoutView();
            HoneySens.request('view:content').main.show(this.rootView);

            // Register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('platforms:show', function() {
                if(!HoneySens.assureAllowed('sensors', 'get')) return false;
                contentRegion.show(new PlatformListView({collection: HoneySens.data.models.platforms}));
                router.navigate('sensors/platforms');
                HoneySens.vent.trigger('platforms:shown');
            });
            HoneySens.reqres.setHandler('platforms:details', function(model) {
                HoneySens.request('view:content').overlay.show(new PlatformDetailsView({model: model}));
            });
            HoneySens.reqres.setHandler('platforms:firmware:add', function() {
                HoneySens.request('view:modal').show(new ModalFirmwareUploadView());
            });
            HoneySens.reqres.setHandler('platforms:firmware:remove', function(model) {
                HoneySens.request('view:modal').show(new ModalFirmwareRemoveView({model: model}));
            });
        },
        stop: function() {
            console.log('Stopping module: platforms');
            HoneySens.reqres.removeHandler('platforms:show');
            HoneySens.reqres.removeHandler('platforms:details');
        },
        routesList: {
            'sensors/platforms': 'showPlatforms'
        },
        showPlatforms: function() {HoneySens.request('platforms:show');}
    });

    return HoneySens.module('Platforms.Routing', PlatformsModule);
});