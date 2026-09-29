import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import { TaskStatus, TaskType, TaskWorkerStatus } from 'app/models';
import Backgrid from 'backgrid';
import TaskListTpl from 'app/modules/tasks/templates/TaskList.tpl';
import TaskListTypeCellTpl from 'app/modules/tasks/templates/TaskListTypeCell.tpl';
import TaskListStatusCellTpl from 'app/modules/tasks/templates/TaskListStatusCell.tpl';
import TaskListActionsCellTpl from 'app/modules/tasks/templates/TaskListActionsCell.tpl';

const TaskList = View.extend({
    template: _.template(TaskListTpl),
    templateContext: {...i18n},
    className: 'row',
    regions: {
        list: 'div.table-responsive'
    },
    onRender: function() {
        this.updateWorkerStatus();
        var columns = [{
            name: 'id',
            label: i18n.t('id'),
            editable: false,
            cell: Backgrid.IntegerCell.extend({
                orderSeparator: ''
            })
        }, {
            name: 'type',
            label: i18n.t('tasks:taskJob'),
            editable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(TaskListTypeCellTpl),
                render: function() {
                    this.$el.html(this.template(_.extend({t: i18n.t, TaskType: TaskType}, this.model.attributes)));
                    return this;
                }
            })
        }, {
            name: 'user',
            label: i18n.t('user'),
            editable: false,
            cell: Backgrid.Cell.extend({
                render: function() {
                    // Tasks are not necessarily associated with a user
                    var userid = this.model.get('user'),
                        sessionUser = HoneySens.data.session.user;
                    if(userid === sessionUser.id) this.$el.html(sessionUser.get("name"));
                    else this.$el.html(userid ? HoneySens.data.models.users.get(userid).get('name') : `(${i18n.t('tasks:userSystem')})`);
                    return this;
                }
            })
        }, {
            name: 'status',
            label: i18n.t('tasks:taskStatus'),
            editable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(TaskListStatusCellTpl),
                render: function() {
                    this.$el.html(this.template(_.extend({t: i18n.t, TaskStatus: TaskStatus}, this.model.attributes)));
                    return this;
                }
            })
        }, {
            label: i18n.t('actions'),
            editable: false,
            sortable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(TaskListActionsCellTpl),
                events: {
                    'click button.removeTask': function(e) {
                        e.preventDefault();
                        this.model.destroy({wait: true});
                    },
                    'click button.downloadTaskResult': function(e) {
                        e.preventDefault();
                        this.model.downloadResult(false);
                    },
                    'click button.inspectUpload': function(e) {
                        e.preventDefault();
                        radio.request('tasks:upload:show', this.model);
                    },
                    'click button.inspectTestMail': function(e) {
                        e.preventDefault();
                        radio.request('tasks:testmail:show', this.model);
                    }
                },
                render: function() {
                    var templateData = this.model.attributes,
                        model = this.model;
                    templateData.isDownloadable = function() {
                        var downloadableTypes = [TaskType.SENSORCFG_CREATOR, TaskType.EVENT_EXTRACTOR];
                        return model.get('status') === TaskStatus.DONE && downloadableTypes.includes(model.get('type'));
                    };
                    this.$el.html(this.template(_.extend({t: i18n.t, TaskStatus: TaskStatus, TaskType: TaskType}, templateData)));
                    this.$el.find('button').tooltip();
                    return this;
                }
            })
        }];
        var grid = new Backgrid.Grid({
            columns: columns,
            collection: this.collection,
            className: 'table table-striped'
        });
        this.getRegion('list').show(grid);
        grid.sort('id', 'descending');
    },
    updateWorkerStatus: function() {
        // Queries task worker status and displays the result
        var status = new TaskWorkerStatus(),
            view = this;
        status.fetch({
            success: function(model) {
                view.$el.find('#taskWorkerStatus').removeClass('statusOffline').addClass('statusOnline').text(`${i18n.t('tasks:workerStatusOnline')}`);
                view.$el.find('#taskWorkerQueueLength').text(model.get('queue_length'));
                view.$el.find('#taskWorkerQueue').removeClass('hidden');
            },
            error: function() {
                view.$el.find('#taskWorkerStatus').removeClass('statusOnline').addClass('statusOffline').text(`${i18n.t('tasks:workerStatusOffline')}`);
            }
        });
    }
});

export default TaskList;
