define(['app/app',
        'app/models',
        'backgrid',
        'app/modules/logs/templates/LogList.tpl',
        'backgrid-paginator',
        'backgrid-select-filter',
        'app/views/common'],
function(HoneySens, Models, Backgrid, LogListTpl) {
    HoneySens.module('Logs.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        function getUserSelectOptions() {
            var users = HoneySens.data.models.users.models;
            return _.union([{label: 'Alle', value: null}],
                _.map(users, function(user) {
                    return {label: user.get('name'), value: user.id};
                })
            );
        }

        function getResourceTypeSelectOptions() {
            return _.union([{label: 'Alle', value: null}],
                _.map(Models.LogEntry.resource, function(rID) {
                    return {label: stringifyResourceType(rID), value: rID};
                })
            );
        }

        function stringifyResourceType(resource_type) {
            switch(resource_type) {
                case Models.LogEntry.resource.GENERIC: return 'Allgemein';
                case Models.LogEntry.resource.CONTACTS: return 'Kontakte';
                case Models.LogEntry.resource.DIVISIONS: return 'Gruppen';
                case Models.LogEntry.resource.EVENTFILTERS: return 'Ereignis-Filter';
                case Models.LogEntry.resource.EVENTS: return 'Ereignisse';
                case Models.LogEntry.resource.PLATFORMS: return 'Plattformen';
                case Models.LogEntry.resource.SENSORS: return 'Sensoren';
                case Models.LogEntry.resource.SERVICES: return 'Dienste';
                case Models.LogEntry.resource.SETTINGS: return 'Konfiguration';
                case Models.LogEntry.resource.TASKS: return 'Prozesse';
                case Models.LogEntry.resource.USERS: return 'Benutzer';
                case Models.LogEntry.resource.SYSTEM: return 'System';
                case Models.LogEntry.resource.SESSIONS: return 'Sessions';
            }
        }

        Views.LogList = Marionette.LayoutView.extend({
            template: _.template(LogListTpl),
            grid: null,
            regions: {
                list: 'div.table-responsive',
                paginator: 'div.paginator',
                resourceFilter: 'div.resourceFilter',
                userFilter: 'div.userFilter'
            },
            onRender: function() {
                var view = this;
                // Adjust page size on viewport changes
                // TODO listen to collection
                $(window).resize(function() {
                    view.refreshPageSize(view.collection);
                });
                // Reset collection (in case some queryParams were set previously)
                delete HoneySens.data.models.logs.queryParams.user_id;
                delete HoneySens.data.models.logs.queryParams.resource_type;

                var columns = [{
                    name: 'id',
                    label: 'ID',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.IntegerCell.extend({
                        orderSeparator: ''
                    })
                }, {
                    name: 'timestamp',
                    label: 'Zeitpunkt',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(HoneySens.Views.EventTemplateHelpers.showTimestamp(this.model.get('timestamp')));
                            return this;
                        }
                    })
                }, {
                    name: 'user_id',
                    label: 'Benutzer',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            var id = this.model.get('user_id'),
                                user = HoneySens.data.models.users.get(id),
                                result = user ? user.get('name') : id;
                            this.$el.html(result);
                            return this;
                        }
                    })
                }, {
                    name: 'resource_type',
                    label: 'Ressource',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(stringifyResourceType(this.model.get('resource_type')));
                            return this;
                        }
                    })
                }, {
                    name: 'resource_id',
                    label: 'RID',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.IntegerCell.extend({
                        orderSeparator: ''
                    })
                }, {
                    name: 'message',
                    label: 'Ereignis',
                    editable: false,
                    sortable: false,
                    cell: 'string'
                }];
                this.grid = new Backgrid.Grid({
                    className: 'table table-striped',
                    collection: this.collection,
                    columns: columns
                });
                var paginator = new Backgrid.Extension.Paginator({
                    collection: this.collection
                });
                this.list.show(this.grid);
                this.paginator.show(paginator);
                // User filter
                this.userFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'user_id',
                    selectOptions: getUserSelectOptions()
                });
                this.userFilter.show(this.userFilterView);
                // Resource filter
                this.resourceFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'resource_type',
                    selectOptions: getResourceTypeSelectOptions()
                });
                this.resourceFilter.show(this.resourceFilterView);
                this.collection.fetch({
                    success: function() {
                        view.refreshPageSize(view.collection);
                    }
                });
            },
            refreshPageSize: function(collection) {
                if(collection.length > 0) {
                    var rowHeight = $('table tbody tr').outerHeight(),
                        curContentHeight = $('nav.navbar').outerHeight(true) + $('#main').height(),
                        availDataSpace = window.innerHeight - curContentHeight + $('table tbody').outerHeight(),
                        pageSize = Math.floor(availDataSpace / rowHeight);
                    if(pageSize >= 1 && pageSize !== collection.state.pageSize) collection.setPageSize(pageSize, {first: false});
                }
            },
        });
    });

    return HoneySens.Logs.Views.LogList;
});