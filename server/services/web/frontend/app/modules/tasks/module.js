import { radio } from 'app/radio';
import HoneySens from 'app/app';
import Models from 'app/models';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/tasks/views/Layout';
import TaskListView from 'app/modules/tasks/views/TaskList';
import FileUploadView from 'app/common/views/FileUpload';
import ModalSendTestMail from 'app/modules/settings/views/ModalSendTestMail';

var TasksModule = createRoutingModule({
    name: 'tasks',
    startWithParent: false,
    rootView: null,
    menuItems: [{
        title: _.t('tasks:header'),
        uri: 'tasks',
        iconClass: 'glyphicon glyphicon-tasks',
        permission: {domain: 'tasks', action: 'get'},
        priority: 4,
        highlight: {
            count: function() {
                var doneTasks = HoneySens.data.models.tasks.where({status: Models.Task.status.DONE}).length,
                    failedTasks = HoneySens.data.models.tasks.where({status: Models.Task.status.ERROR}).length;
                return doneTasks + failedTasks;
            },
            getModel: function() {
                return HoneySens.data.models.tasks
            },
            event: 'update'
        }
    }],
    start: function() {
        console.log('Starting module: tasks');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // Register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('tasks:show', function() {
            if(!HoneySens.assureAllowed('tasks', 'get')) return false;
            contentRegion.show(new TaskListView({collection: HoneySens.data.models.tasks}));
            router.navigate('tasks');
            radio.trigger('tasks:shown');
        });
        radio.reply('tasks:upload:show', function(model) {
            radio.request('view:content').getRegion('overlay').show(new FileUploadView({model: model}));
        });
        radio.reply('tasks:testmail:show', function(model) {
            radio.request('view:modal').show(new ModalSendTestMail({model: model}));
        });
    },
    stop: function() {
        console.log('Stopping module: tasks');
        radio.stopReplying('tasks:show');
    },
    routesList: {
        'tasks': 'showTasks'
    },
    showTasks: function() {radio.request('tasks:show');}
});

export default HoneySens.module('Tasks.Routing', TasksModule);