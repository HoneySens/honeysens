define(['app/app', 'app/routing', 'app/models',
        'app/modules/sensors/views/Layout',
        'app/modules/sensors/views/SensorList',
        'app/modules/sensors/views/SensorEdit',
        'app/modules/sensors/views/ModalSensorRemove',
        'app/modules/tasks/views/ModalAwaitTask',
        'app/common/views/ModalServerError'],
function(HoneySens, Routing, Models, LayoutView, SensorListView, SensorEditView, ModalSensorRemoveView, ModalAwaitTaskView, ModalServerError) {
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
            HoneySens.reqres.setHandler('sensors:config:download', function(model) {
                $.ajax({
                    type: 'GET',
                    url: 'api/sensors/config/' + model.id,
                    dataType: 'json',
                    success: function(resp) {
                        var task = HoneySens.data.models.tasks.add(new Models.Task(resp)),
                            awaitTaskView = new ModalAwaitTaskView({model: task});
                        HoneySens.request('view:modal').show(awaitTaskView);
                        HoneySens.Views.waitForTask(task, {
                            done: function(task) {
                                if(!awaitTaskView.isDestroyed) {
                                    // Close modal view and start download, then remove the task
                                    task.downloadResult(true);
                                    awaitTaskView.destroy();
                                }
                            },
                            error: function(task) {
                                if(!awaitTaskView.isDestroyed) {
                                    // In case there was an error, remove the task immediately
                                    task.destroy({wait: true});
                                }
                            }
                        });
                    },
                    error: function() {
                        HoneySens.request('view:modal').show(new ModalServerError({
                            model: new Backbone.Model({msg: 'Serverfehler beim Erzeugen der Sensorkonfiguration'})
                        }));
                    }
                })
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