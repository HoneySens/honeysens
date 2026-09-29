import i18n from 'app/common/i18n';
import {View} from 'backbone.marionette';
import HoneySens from 'app/app';
import { LogEntryResource } from 'app/models';
import Backgrid from 'backgrid';
import LogListTpl from 'app/modules/logs/templates/LogList.tpl';
import 'backgrid-paginator';
import 'backgrid-select-filter';
import {EventTemplateHelpers} from 'app/views/common';

function getUserSelectOptions() {
    var users = HoneySens.data.models.users.models;
    return _.union([{label: i18n.t('all'), value: null}],
        _.map(users, function(user) {
            return {label: user.get('name'), value: user.id};
        })
    );
}

function getResourceTypeSelectOptions() {
    return _.union([{label: i18n.t('all'), value: null}],
        _.map(LogEntryResource, function(rID) {
            return {label: stringifyResourceType(rID), value: rID};
        })
    );
}

function stringifyResourceType(resource_type) {
    switch(resource_type) {
        case LogEntryResource.GENERIC: return i18n.t('logs:resourceGeneric');
        case LogEntryResource.CONTACTS: return i18n.t('logs:resourceContacts');
        case LogEntryResource.DIVISIONS: return i18n.t('logs:resourceDivisions');
        case LogEntryResource.EVENTFILTERS: return i18n.t('logs:resourceFilters');
        case LogEntryResource.EVENTS: return i18n.t('events');
        case LogEntryResource.PLATFORMS: return i18n.t('logs:resourcePlatforms');
        case LogEntryResource.SENSORS: return i18n.t('sensors');
        case LogEntryResource.SERVICES: return i18n.t('logs:resourceServices');
        case LogEntryResource.SETTINGS: return i18n.t('logs:resourceSettings');
        case LogEntryResource.TASKS: return i18n.t('logs:resourceTasks');
        case LogEntryResource.USERS: return i18n.t('users');
        case LogEntryResource.SYSTEM: return i18n.t('logs:resourceSystem');
        case LogEntryResource.SESSIONS: return i18n.t('logs:resourceSessions');
    }
}

const LogList = View.extend({
    template: _.template(LogListTpl),
    templateContext: {...i18n},
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
            label: i18n.t('id'),
            editable: false,
            sortable: false,
            cell: Backgrid.IntegerCell.extend({
                orderSeparator: ''
            })
        }, {
            name: 'timestamp',
            label: i18n.t('timestamp'),
            editable: false,
            sortable: false,
            cell: Backgrid.Cell.extend({
                render: function() {
                    this.$el.html(EventTemplateHelpers.showTimestamp(this.model.get('timestamp')));
                    return this;
                }
            })
        }, {
            name: 'user_id',
            label: i18n.t('user'),
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
            label: i18n.t('logs:resource'),
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
            label: i18n.t('logs:resourceID'),
            editable: false,
            sortable: false,
            cell: Backgrid.IntegerCell.extend({
                orderSeparator: ''
            })
        }, {
            name: 'message',
            label: i18n.t('event'),
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
        this.getRegion('list').show(this.grid);
        this.getRegion('paginator').show(paginator);
        // User filter
        this.userFilterView = new Backgrid.Extension.SelectFilter({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'user_id',
            selectOptions: getUserSelectOptions()
        });
        this.getRegion('userFilter').show(this.userFilterView);
        // Resource filter
        this.resourceFilterView = new Backgrid.Extension.SelectFilter({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'resource_type',
            selectOptions: getResourceTypeSelectOptions()
        });
        this.getRegion('resourceFilter').show(this.resourceFilterView);
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

export default LogList;
