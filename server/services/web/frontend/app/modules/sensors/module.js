import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import Models from 'app/models';
import LayoutView from 'app/modules/sensors/views/Layout';
import SensorListView from 'app/modules/sensors/views/SensorList';
import SensorEditView from 'app/modules/sensors/views/SensorEdit';
import ModalSensorRemoveView from 'app/modules/sensors/views/ModalSensorRemove';
import ModalAwaitTaskView from 'app/modules/tasks/views/ModalAwaitTask';
import ModalServerError from 'app/common/views/ModalServerError';

var SensorsModule = createRoutingModule({
    name: 'sensors',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: _.t('sensors:header'), uri: 'sensors', iconClass: 'glyphicon glyphicon-hdd', permission: {domain: 'sensors', action: 'get'}, priority: 2}
    ],
    start: function() {
        console.log('Starting module: sensors');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('sensors:show', function() {
            if(!HoneySens.assureAllowed('sensors', 'get')) return false;
            contentRegion.show(new SensorListView({collection: HoneySens.data.models.sensors}));
            router.navigate('sensors');
            radio.trigger('sensors:shown');
        });
        radio.reply('sensors:add', function() {
            radio.request('view:content').getRegion('overlay').show(new SensorEditView({model: new Models.Sensor()}));
        });
        radio.reply('sensors:edit', function(model) {
            radio.request('view:content').getRegion('overlay').show(new SensorEditView({model: model}));
        });
        radio.reply('sensors:remove', function(model) {
            radio.request('view:modal').show(new ModalSensorRemoveView({model: model}));
        });
        radio.reply('sensors:config:download', function(model) {
            $.ajax({
                type: 'GET',
                url: 'api/sensors/config/' + model.id,
                dataType: 'json',
                success: function(resp) {
                    var task = HoneySens.data.models.tasks.add(new Models.Task(resp)),
                        awaitTaskView = new ModalAwaitTaskView({model: task});
                    radio.request('view:modal').show(awaitTaskView);
                    HoneySens.Views.waitForTask(task, {
                        done: function(task) {
                            if(!awaitTaskView.isDestroyed()) {
                                // Close modal view and start download, then remove the task
                                task.downloadResult(true);
                                awaitTaskView.destroy();
                            }
                        },
                        error: function(task) {
                            if(!awaitTaskView.isDestroyed()) {
                                // In case there was an error, remove the task immediately
                                task.destroy({wait: true});
                            }
                        }
                    });
                },
                error: function() {
                    radio.request('view:modal').show(new ModalServerError({
                        model: new Backbone.Model({msg: _.t('sensors:sensorConfigError')})
                    }));
                }
            })
        });
    },
    stop: function() {
        console.log('Stopping module: sensors');
        radio.stopReplying('sensors:show');
        radio.stopReplying('sensors:add');
        radio.stopReplying('sensors:edit');
        radio.stopReplying('sensors:remove');
    },
    routesList: {
        'sensors': 'showSensors'
    },
    showSensors: function() {radio.request('sensors:show');},
});

export default HoneySens.module('Sensors.Routing', SensorsModule);