define(['app/app', 'app/routing', 'app/models',
        'app/modules/sensors/views/Layout',
        'app/modules/sensors/views/SensorList',
        'app/modules/sensors/views/SensorEdit',
        'app/modules/sensors/views/ModalSensorRemove'],
function(HoneySens, Routing, Models, LayoutView, SensorListView, SensorEditView, ModalSensorRemoveView) {
    var SensorsModule = Routing.extend({
        name: 'sensors',
        startWithParent: false,
        rootView: null,
        menuItems: [
            {title: 'Sensoren', uri: 'sensors', iconClass: 'glyphicon glyphicon-hdd', permission: {domain: 'sensors', action: 'get'}, priority: 2}
        ],
        start: function() {
            console.log('Starting module: sensors');
            this.rootView = new LayoutView();
            HoneySens.request('view:content').main.show(this.rootView);

            // Register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('sensors:show', function() {
                if(!HoneySens.assureAllowed('sensors', 'get')) return false;
                contentRegion.show(new SensorListView({collection: HoneySens.data.models.sensors}));
                router.navigate('sensors');
                HoneySens.vent.trigger('sensors:shown');
            });
            HoneySens.reqres.setHandler('sensors:add', function() {
                HoneySens.request('view:content').overlay.show(new SensorEditView({model: new Models.Sensor()}));
            });
            HoneySens.reqres.setHandler('sensors:edit', function(model) {
                HoneySens.request('view:content').overlay.show(new SensorEditView({model: model}));
            });
            HoneySens.reqres.setHandler('sensors:remove', function(model) {
                HoneySens.request('view:modal').show(new ModalSensorRemoveView({model: model}));
            });
        },
        stop: function() {
            console.log('Stopping module: sensors');
            HoneySens.reqres.removeHandler('sensors:show');
            HoneySens.reqres.removeHandler('sensors:add');
            HoneySens.reqres.removeHandler('sensors:edit');
            HoneySens.reqres.removeHandler('sensors:remove');
        },
        routesList: {
            'sensors': 'showSensors'
        },
        showSensors: function() {HoneySens.request('sensors:show');},
    });

    return HoneySens.module('Sensors.Routing', SensorsModule);
});