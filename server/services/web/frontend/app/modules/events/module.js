import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import Models from 'app/models';
import Backbone from 'backbone';
import LayoutView from 'app/modules/events/views/Layout';
import EventListView from 'app/modules/events/views/EventList';
import EventEditView from 'app/modules/events/views/EventEdit';
import FilterListView from 'app/modules/events/views/FilterList';
import FilterEditView from 'app/modules/events/views/FilterEdit';
import ModalFilterRemoveView from 'app/modules/events/views/ModalFilterRemove';
import ModalEventRemoveView from 'app/modules/events/views/ModalEventRemove';
import ModalAwaitTaskView from 'app/modules/tasks/views/ModalAwaitTask';

function calculateEventQueryParams(params) {
    // Takes an params object as provided in collection.queryParams, calculates and returns its actual values
    var result = _.clone(params);
    // Clean up and assemble parameters
    delete result.currentPage;
    delete result.pageSize;
    delete result.totalPages;
    delete result.totalRecords;
    delete result.sortKey;
    delete result.order;
    delete result.directions;
    // Assign the return values of function params
    _.each(_.clone(result), function(param, key) {
        if(_.isFunction(param)) {
            let paramVal = param();
            if(paramVal !== null) result[key] = paramVal;
            else delete result[key];
        }
    });
    return result;
}

var EventsModule = createRoutingModule({
    name: 'events',
    startWithParent: false,
    rootView: null,
    menuItems: [{
        title: _.t('events:eventHeader'),
        uri: 'events',
        iconClass: 'glyphicon glyphicon-list',
        permission: {domain: 'events', action: 'get'},
        priority: 1,
        highlight: {
            count: function() {
                return HoneySens.data.models.new_events.length;
            },
            getModel: function() {
                return HoneySens.data.models.new_events;
            },
            event: 'update'
        }
    }, {
        title: _.t('events:filterHeader'),
        uri: 'events/filters',
        iconClass: 'glyphicon glyphicon-filter',
        permission: {domain: 'eventfilters', action: 'create'}
    }],
    start: function() {
        console.log('Starting module: event');
        var module = this;
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('events:show', function() {
            if(!HoneySens.assureAllowed('events', 'get')) return false;
            contentRegion.show(new EventListView({collection: HoneySens.data.models.events}));
            radio.trigger('events:shown');
            router.navigate('events');
        });
        radio.reply('events:filters:show', function() {
            contentRegion.show(new FilterListView({collection: HoneySens.data.models.eventfilters}));
            radio.trigger('events:filters:shown');
            router.navigate('events/filters');
        });
        radio.reply('events:filters:add', function() {
            if(!HoneySens.assureAllowed('eventfilters', 'create')) return false;
            radio.request('view:content').getRegion('overlay').show(new FilterEditView({model: new Models.EventFilter()}));
        });
        radio.reply('events:filters:toggle', function(filter) {
            if(!HoneySens.assureAllowed('eventfilters', 'update')) return false;
            filter.save({enabled: !filter.get('enabled')}, {wait: true});
        });

        radio.reply('events:filters:edit', function(filter) {
            if(!HoneySens.assureAllowed('eventfilters', 'update')) return false;
            radio.request('view:content').getRegion('overlay').show(new FilterEditView({model: filter}));
        });
        radio.reply('events:filters:remove', function(filter) {
            radio.request('view:modal').show(new ModalFilterRemoveView({model: filter}));
        });
        radio.reply('events:export:all', function(collection) {
            module.exportEvents(collection, collection.queryParams);
        });
        radio.reply('events:export:page', function(collection) {
            // Export currently displayed page, that is all events currently within the collection
            var params = _.clone(collection.queryParams);
            params.list = collection.pluck('id');
            module.exportEvents(collection, params);
        });
        radio.reply('events:export:list', function(collection, events) {
            // Pluck query params from the collection, but use the event collection as actual event list
            var params = _.clone(collection.queryParams);
            params.list = events.pluck('id');
            module.exportEvents(collection, params);
        });
        radio.reply('events:edit:single', function(model) {
            if(!HoneySens.assureAllowed('events', 'update')) return false;
            var dialog = new EventEditView({model: model});
            dialog.listenTo(dialog, 'confirm', function(data) {
                // Only send a request in case something was modified
                if(Object.keys(data).length === 0) {
                    radio.request('view:content').getRegion('overlay').empty();
                    return;
                }
                $.ajax({
                    type: 'PUT',
                    url: 'api/events/' + model.id,
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function() {
                        HoneySens.data.models.events.fetch();
                        radio.request('view:content').getRegion('overlay').empty();
                    }
                });
            });
            radio.request('view:content').getRegion('overlay').show(dialog);
        });
        radio.reply('events:edit:all', function(collection) {
            if(!HoneySens.assureAllowed('events', 'update')) return false;
            var dialog = new EventEditView({model: new Backbone.Model({total: collection.state.totalRecords})});
            dialog.listenTo(dialog, 'confirm', function(data) {
                module.updateEvents(collection.queryParams, data, function() {
                    HoneySens.data.models.events.fetch();
                    radio.request('view:content').getRegion('overlay').empty();
                });
            });
            radio.request('view:content').getRegion('overlay').show(dialog);
        });
        radio.reply('events:edit:some', function(selection) {
            if(!HoneySens.assureAllowed('events', 'update') || selection.length === 0) return false;
            var dialog = new EventEditView({model: new Backbone.Model({total: selection.length})});
            dialog.listenTo(dialog, 'confirm', function(data) {
                // Only send a request in case something was modified in the dialog
                if(Object.keys(data).length > 0) {
                    data.ids = selection.pluck('id');
                    $.ajax({
                        type: 'PUT',
                        url: 'api/events',
                        data: JSON.stringify(data),
                        contentType: 'application/json',
                        success: function() {
                            HoneySens.data.models.events.fetch();
                            radio.request('view:content').getRegion('overlay').empty();
                        }
                    });
                } else radio.request('view:content').getRegion('overlay').empty();
            });
            radio.request('view:content').getRegion('overlay').show(dialog);
        });
        radio.reply('events:remove:all', function(collection) {
            if(!HoneySens.assureAllowed('events', 'delete')) return false;
            let archived = collection.queryParams.hasOwnProperty('archived') && collection.queryParams.archived,
                dialog = new ModalEventRemoveView({model: new Backbone.Model({archived: archived, total: collection.state.totalRecords})});
            dialog.listenTo(dialog, 'confirm', function(archive) {
                module.removeEvents(collection.queryParams, archive, function() {
                    HoneySens.data.models.events.fetch();
                    radio.request('view:modal').empty();
                });
            });
            radio.request('view:modal').show(dialog);
        });
        radio.reply('events:remove:some', function(selection, fullCollection) {
            if(selection.length === 0) return false;
            let archived = fullCollection.queryParams.hasOwnProperty('archived') && fullCollection.queryParams.archived,
                dialog = new ModalEventRemoveView({model: new Backbone.Model({archived: archived, total: selection.length})});
            dialog.listenTo(dialog, 'confirm', function(archive) {
                // Avoid RangeError when deleting all events of the last page (if currently displayed)
                if(fullCollection.state.currentPage > 0 &&
                    fullCollection.state.currentPage + 1 === fullCollection.state.totalPages &&
                    _.difference(fullCollection.pluck('id'), selection.pluck('id')).length === 0) {
                    fullCollection.getPreviousPage();
                }
                // Send request
                $.ajax({
                    type: 'DELETE',
                    url: 'api/events',
                    data: JSON.stringify({ids: selection.pluck('id'), archived: archived, archive: archive}),
                    contentType: 'application/json',
                    success: function() {
                        HoneySens.data.models.events.fetch();
                        radio.request('view:modal').empty();
                    }
                });
            });
            radio.request('view:modal').show(dialog);
        });
    },
    stop: function() {
        console.log('Stopping module: events');
        radio.stopReplying('events:show');
        radio.stopReplying('events:filters:show');
        radio.stopReplying('events:filters:add');
        radio.stopReplying('events:filter:edit');
        radio.stopReplying('events:filter:remove');
    },
    routesList: {
        'events': 'showEvents',
        'events/filters': 'showFilters'
    },
    showEvents: function() {radio.request('events:show');},
    showFilters: function() {radio.request('events:filters:show');},
    exportEvents: function(collection, params) {
        var calcParams = calculateEventQueryParams(params);
        // Sorting
        if(collection.state.sortKey != null) {
            calcParams[collection.queryParams.sortKey] = collection.state.sortKey;
            calcParams[collection.queryParams.order] = collection.queryParams.directions[collection.state.order];
        }
        calcParams.format = 'text/csv';
        $.ajax({
            type: 'GET',
            url: 'api/events',
            data: calcParams,
            dataType: 'json',
            success: function(res) {
                var task = HoneySens.data.models.tasks.add(new Models.Task(res)),
                    awaitTaskView = new ModalAwaitTaskView({model: task});
                radio.request('view:modal').show(awaitTaskView);
                HoneySens.Views.waitForTask(task, {
                    done: function(m) {
                        if(!awaitTaskView.isDestroyed()) {
                            // Close modal view and start download, then remove the task
                            m.downloadResult(true);
                            awaitTaskView.destroy();
                        }
                    },
                    error: function(m) {
                        if(!awaitTaskView.isDestroyed()) {
                            // In case there was an error, remove the task immediately
                            m.destroy({wait: true});
                        }
                    }
                });
            }
        });
    },
    updateEvents: function(queryParams, eventData, success) {
        // Only fire a request in case event data was submitted
        if(Object.keys(eventData).length > 0)
            $.ajax({
                type: 'PUT',
                url: 'api/events',
                data: JSON.stringify(Object.assign(calculateEventQueryParams(queryParams), eventData)),
                contentType: 'application/json',
                dataType: 'json',
                success: success
            });
        else success();
    },
    removeEvents: function(queryParams, archive, success) {
        $.ajax({
            type: 'DELETE',
            url: 'api/events',
            data: JSON.stringify(Object.assign(calculateEventQueryParams(queryParams), {archived: queryParams.hasOwnProperty('archived') && queryParams.archived, archive: archive})),
            contentType: 'application/json',
            dataType: 'json',
            success: success
        });
    }
});

export default HoneySens.module('Events.Routing', EventsModule);