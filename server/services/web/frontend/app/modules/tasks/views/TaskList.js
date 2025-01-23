import HoneySens from 'app/app';
import Models from 'app/models';
import Backgrid from 'backgrid';
import TaskListTpl from 'app/modules/tasks/templates/TaskList.tpl';
import TaskListTypeCellTpl from 'app/modules/tasks/templates/TaskListTypeCell.tpl';
import TaskListStatusCellTpl from 'app/modules/tasks/templates/TaskListStatusCell.tpl';
import TaskListActionsCellTpl from 'app/modules/tasks/templates/TaskListActionsCell.tpl';

HoneySens.module('Tasks.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.TaskList = Marionette.LayoutView.extend({
        template: _.template(TaskListTpl),
        className: 'row',
        regions: {
            list: 'div.table-responsive'
        },
        onRender: function() {
            this.updateWorkerStatus();
            var columns = [{
                name: 'id',
                label: 'ID',
                editable: false,
                cell: Backgrid.IntegerCell.extend({
                    orderSeparator: ''
                })
            }, {
                name: 'type',
                label: 'Job',
                editable: false,
                cell: Backgrid.Cell.extend({
                    template: _.template(TaskListTypeCellTpl),
                    render: function() {
                        this.$el.html(this.template(this.model.attributes));
                        return this;
                    }
                })
            }, {
                name: 'user',
                label: 'Benutzer',
                editable: false,
                cell: Backgrid.Cell.extend({
                    render: function() {
                        // Tasks are not necessarily associated with a user
                        var userid = this.model.get('user'),
                            sessionUser = HoneySens.data.session.user;
                        if(userid === sessionUser.id) this.$el.html(sessionUser.get("name"));
                        else this.$el.html(userid ? HoneySens.data.models.users.get(userid).get('name') : '(system)');
                        return this;
                    }
                })
            }, {
                name: 'status',
                label: 'Status',
                editable: false,
                cell: Backgrid.Cell.extend({
                    template: _.template(TaskListStatusCellTpl),
                    render: function() {
                        this.$el.html(this.template(this.model.attributes));
                        return this;
                    }
                })
            }, {
                label: 'Aktionen',
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
                            HoneySens.request('tasks:upload:show', this.model);
                        },
                        'click button.inspectTestMail': function(e) {
                            e.preventDefault();
                            HoneySens.request('tasks:testmail:show', this.model);
                        }
                    },
                    render: function() {
                        var templateData = this.model.attributes,
                            model = this.model;
                        templateData.isDownloadable = function() {
                            var downloadableTypes = [Models.Task.type.SENSORCFG_CREATOR, Models.Task.type.EVENT_EXTRACTOR];
                            return model.get('status') === Models.Task.status.DONE && downloadableTypes.includes(model.get('type'));
                        };
                        this.$el.html(this.template(templateData));
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
            this.list.show(grid);
            grid.sort('id', 'descending');
        },
        updateWorkerStatus: function() {
            // Queries task worker status and displays the result
            var status = new Models.TaskWorkerStatus(),
                view = this;
            status.fetch({
                success: function(model) {
                    view.$el.find('#taskWorkerStatus').removeClass('statusOffline').addClass('statusOnline').text('Online');
                    view.$el.find('#taskWorkerQueueLength').text(model.get('queue_length'));
                    view.$el.find('#taskWorkerQueue').removeClass('hidden');
                },
                error: function() {
                    view.$el.find('#taskWorkerStatus').removeClass('statusOnline').addClass('statusOffline').text('Offline');
                }
            });
        }
    });
});

export default HoneySens.Tasks.Views.TaskList;