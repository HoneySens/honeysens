import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import { TaskStatus, TaskType } from 'app/models';
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
        title: i18n.t('tasks:header'),
        uri: 'tasks',
        iconClass: 'glyphicon glyphicon-tasks',
        permission: {domain: 'tasks', action: 'get'},
        priority: 4,
        highlight: {
            count: function() {
                var doneTasks = HoneySens.data.models.tasks.where({status: TaskStatus.DONE}).length,
                    failedTasks = HoneySens.data.models.tasks.where({status: TaskStatus.ERROR}).length;
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
            radio.trigger('tasks:shown');
        });
        radio.reply('tasks:edit', function(task) {
            if(!HoneySens.assureAllowed('tasks', 'update')) return false;
            switch(task.get('type')) {
                case TaskType.UPLOAD_VERIFIER:
                    radio.request('view:content').getRegion('overlay').showOverlay(
                        new FileUploadView({model: task}),
                        'tasks/' + task.id,
                        'tasks',
                        router);
                    break;
                case TaskType.EMAIL_EMITTER:
                    radio.request('view:modal').show(new ModalSendTestMail({model: task}));
                    break;
            }
        });
        radio.reply('tasks:upload:show', function(model) {
        });
        radio.reply('tasks:testmail:show', function(model) {
        });
    },
    stop: function() {
        console.log('Stopping module: tasks');
        radio.stopReplying('tasks:show');
        radio.stopReplying("tasks:edit");
    },
    routesList: {
        'tasks': 'showTasks',
        'tasks/:id': 'editTask'
    },
    showTasks: function() {
        radio.request('tasks:show');
        this.router.navigate('tasks');
    },
    editTask: function(id) {
        radio.request('tasks:show');
        radio.request('tasks:edit', HoneySens.data.models.tasks.get(id));
    }
});

export default TasksModule;