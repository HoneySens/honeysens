import HoneySens from 'app/app';
import Routing from 'app/routing';
import Models from 'app/models';
import LayoutView from 'app/modules/sensors/views/Layout';
import SensorListView from 'app/modules/sensors/views/SensorList';
import SensorEditView from 'app/modules/sensors/views/SensorEdit';
import ModalSensorRemoveView from 'app/modules/sensors/views/ModalSensorRemove';
import ModalAwaitTaskView from 'app/modules/tasks/views/ModalAwaitTask';
import ModalServerError from 'app/common/views/ModalServerError';

var SensorsModule = Routing.extend({
    name: 'sensors',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: _.t('sensors:header'), uri: 'sensors', iconClass: 'glyphicon glyphicon-hdd', permission: {domain: 'sensors', action: 'get'}, priority: 2}
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
                        model: new Backbone.Model({msg: _.t('sensors:sensorConfigError')})
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

export default HoneySens.module('Sensors.Routing', SensorsModule);