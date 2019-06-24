define(['app/app',
        'app/models',
        'app/routing',
        'app/modules/tasks/views/Layout',
        'app/modules/tasks/views/TaskList',
        'app/common/views/FileUpload'],
function(HoneySens, Models, Routing, LayoutView, TaskListView, FileUploadView) {
    var TasksModule = Routing.extend({
        name: 'tasks',
        startWithParent: false,
        rootView: null,
        menuItems: [{
            title: 'Prozesse',
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
            HoneySens.request('view:content').main.show(this.rootView);

            // Register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('tasks:show', function() {
                if(!HoneySens.assureAllowed('tasks', 'get')) return false;
                contentRegion.show(new TaskListView({collection: HoneySens.data.models.tasks}));
                router.navigate('tasks');
                HoneySens.vent.trigger('tasks:shown');
            });
            HoneySens.reqres.setHandler('tasks:upload:show', function(model) {
                HoneySens.request('view:content').overlay.show(new FileUploadView({model: model}));
            });
        },
        stop: function() {
            console.log('Stopping module: tasks');
            HoneySens.reqres.removeHandler('tasks:show');
        },
        routesList: {
            'tasks': 'showTasks'
        },
        showTasks: function() {HoneySens.request('tasks:show');}
    });

    return HoneySens.module('Tasks.Routing', TasksModule);
});