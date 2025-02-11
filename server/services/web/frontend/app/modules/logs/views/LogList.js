import HoneySens from 'app/app';
import Models from 'app/models';
import Backgrid from 'backgrid';
import LogListTpl from 'app/modules/logs/templates/LogList.tpl';
import 'backgrid-paginator';
import 'backgrid-select-filter';
import 'app/views/common';

HoneySens.module('Logs.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    function getUserSelectOptions() {
        var users = HoneySens.data.models.users.models;
        return _.union([{label: _.t('all'), value: null}],
            _.map(users, function(user) {
                return {label: user.get('name'), value: user.id};
            })
        );
    }

    function getResourceTypeSelectOptions() {
        return _.union([{label: _.t('all'), value: null}],
            _.map(Models.LogEntry.resource, function(rID) {
                return {label: stringifyResourceType(rID), value: rID};
            })
        );
    }

    function stringifyResourceType(resource_type) {
        switch(resource_type) {
            case Models.LogEntry.resource.GENERIC: return _.t('logs:resourceGeneric');
            case Models.LogEntry.resource.CONTACTS: return _.t('logs:resourceContacts');
            case Models.LogEntry.resource.DIVISIONS: return _.t('logs:resourceDivisions');
            case Models.LogEntry.resource.EVENTFILTERS: return _.t('logs:resourceFilters');
            case Models.LogEntry.resource.EVENTS: return _.t('events');
            case Models.LogEntry.resource.PLATFORMS: return _.t('logs:resourcePlatforms');
            case Models.LogEntry.resource.SENSORS: return _.t('sensors');
            case Models.LogEntry.resource.SERVICES: return _.t('logs:resourceServices');
            case Models.LogEntry.resource.SETTINGS: return _.t('logs:resourceSettings');
            case Models.LogEntry.resource.TASKS: return _.t('logs:resourceTasks');
            case Models.LogEntry.resource.USERS: return _.t('users');
            case Models.LogEntry.resource.SYSTEM: return _.t('logs:resourceSystem');
            case Models.LogEntry.resource.SESSIONS: return _.t('logs:resourceSessions');
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
                label: _.t('id'),
                editable: false,
                sortable: false,
                cell: Backgrid.IntegerCell.extend({
                    orderSeparator: ''
                })
            }, {
                name: 'timestamp',
                label: _.t('timestamp'),
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
                label: _.t('user'),
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
                label: _.t('logs:resource'),
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
                label: _.t('logs:resourceID'),
                editable: false,
                sortable: false,
                cell: Backgrid.IntegerCell.extend({
                    orderSeparator: ''
                })
            }, {
                name: 'message',
                label: _.t('event'),
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

export default HoneySens.Logs.Views.LogList;