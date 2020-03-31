define(['app/app',
        'app/models',
        'backgrid',
        'tpl!app/modules/tasks/templates/TaskList.tpl',
        'tpl!app/modules/tasks/templates/TaskListTypeCell.tpl',
        'tpl!app/modules/tasks/templates/TaskListStatusCell.tpl',
        'tpl!app/modules/tasks/templates/TaskListActionsCell.tpl'],
function(HoneySens, Models, Backgrid, TaskListTpl, TaskListTypeCellTpl, TaskListStatusCellTpl, TaskListActionsCellTpl) {
    HoneySens.module('Tasks.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.TaskList = Marionette.LayoutView.extend({
            template: TaskListTpl,
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
                        template: TaskListTypeCellTpl,
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
                            var user = this.model.get('user');
                            this.$el.html(user ? HoneySens.data.models.users.get(user).get('name') : '(system)');
                            return this;
                        }
                    })
                }, {
                    name: 'status',
                    label: 'Status',
                    editable: false,
                    cell: Backgrid.Cell.extend({
                        template: TaskListStatusCellTpl,
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
                        template: TaskListActionsCellTpl,
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
                var view = this;
                $.ajax({
                    type: 'GET',
                    url: 'api/tasks/status',
                    success: function() {
                        view.$el.find('div.headerBar span.help-block').removeClass('statusOffline').addClass('statusOnline').text('Online');
                    },
                    error: function() {
                        view.$el.find('div.headerBar span.help-block').removeClass('statusOnline').addClass('statusOffline').text('Offline');
                    }
                });
            }
        });
    });

    return HoneySens.Tasks.Views.TaskList;
});