define(['app/app', 'app/routing', 'app/models', 'backbone',
        'app/modules/events/views/Layout',
        'app/modules/events/views/EventList',
        'app/modules/events/views/EventEdit',
        'app/modules/events/views/FilterList',
        'app/modules/events/views/FilterEdit',
        'app/modules/events/views/ModalFilterRemove',
        'app/modules/events/views/ModalEventRemove',
        'app/modules/tasks/views/ModalAwaitTask'],
function(HoneySens, Routing, Models, Backbone, LayoutView, EventListView, EventEditView, FilterListView, FilterEditView,
         ModalFilterRemoveView, ModalEventRemoveView, ModalAwaitTaskView) {

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

    var EventsModule = Routing.extend({
        name: 'events',
        startWithParent: false,
        rootView: null,
        menuItems: [{
            title: 'Ereignisse',
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
            title: 'Filter',
            uri: 'events/filters',
            iconClass: 'glyphicon glyphicon-filter',
            permission: {domain: 'eventfilters', action: 'create'}
        }],
        start: function() {
            console.log('Starting module: event');
            var module = this;
            this.rootView = new LayoutView();
            HoneySens.request('view:content').main.show(this.rootView);

            // register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('events:show', function() {
                if(!HoneySens.assureAllowed('events', 'get')) return false;
                contentRegion.show(new EventListView({collection: HoneySens.data.models.events}));
                HoneySens.vent.trigger('events:shown');
                router.navigate('events');
            });
            HoneySens.reqres.setHandler('events:filters:show', function() {
                contentRegion.show(new FilterListView({collection: HoneySens.data.models.eventfilters}));
                HoneySens.vent.trigger('events:filters:shown');
                router.navigate('events/filters');
            });
            HoneySens.reqres.setHandler('events:filters:add', function() {
                if(!HoneySens.assureAllowed('eventfilters', 'create')) return false;
                HoneySens.request('view:content').overlay.show(new FilterEditView({model: new Models.EventFilter()}));
            });
            HoneySens.reqres.setHandler('events:filters:toggle', function(filter) {
                if(!HoneySens.assureAllowed('eventfilters', 'update')) return false;
                filter.save({enabled: !filter.get('enabled')}, {wait: true});
            });

            HoneySens.reqres.setHandler('events:filters:edit', function(filter) {
                if(!HoneySens.assureAllowed('eventfilters', 'update')) return false;
                HoneySens.request('view:content').overlay.show(new FilterEditView({model: filter}));
            });
            HoneySens.reqres.setHandler('events:filters:remove', function(filter) {
                HoneySens.request('view:modal').show(new ModalFilterRemoveView({model: filter}));
            });
            HoneySens.reqres.setHandler('events:export:all', function(collection) {
                module.exportEvents(collection, collection.queryParams);
            });
            HoneySens.reqres.setHandler('events:export:page', function(collection) {
                // Export currently displayed page, that is all events currently within the collection
                var params = _.clone(collection.queryParams);
                params.list = collection.pluck('id');
                module.exportEvents(collection, params);
            });
            HoneySens.reqres.setHandler('events:export:list', function(collection, events) {
                // Pluck query params from the collection, but use the event collection as actual event list
                var params = _.clone(collection.queryParams);
                params.list = events.pluck('id');
                module.exportEvents(collection, params);
            });
            HoneySens.reqres.setHandler('events:edit:single', function(model) {
                if(!HoneySens.assureAllowed('events', 'update')) return false;
                var dialog = new EventEditView({model: model});
                dialog.listenTo(dialog, 'confirm', function(data) {
                    // Only send a request in case something was modified
                    if(Object.keys(data).length === 0) {
                        HoneySens.request('view:content').overlay.empty();
                        return;
                    }
                    $.ajax({
                        type: 'PUT',
                        url: 'api/events/' + model.id,
                        data: JSON.stringify(data),
                        contentType: 'application/json',
                        success: function() {
                            HoneySens.data.models.events.fetch();
                            HoneySens.request('view:content').overlay.empty();
                        }
                    });
                });
                HoneySens.request('view:content').overlay.show(dialog);
            });
            HoneySens.reqres.setHandler('events:edit:all', function(collection) {
                if(!HoneySens.assureAllowed('events', 'update')) return false;
                var dialog = new EventEditView({model: new Backbone.Model({total: collection.state.totalRecords})});
                dialog.listenTo(dialog, 'confirm', function(data) {
                    module.updateEvents(collection.queryParams, data, function() {
                        HoneySens.data.models.events.fetch();
                        HoneySens.request('view:content').overlay.empty();
                    });
                });
                HoneySens.request('view:content').overlay.show(dialog);
            });
            HoneySens.reqres.setHandler('events:edit:some', function(selection) {
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
                                HoneySens.request('view:content').overlay.empty();
                            }
                        });
                    } else HoneySens.request('view:content').overlay.empty();
                });
                HoneySens.request('view:content').overlay.show(dialog);
            });
            HoneySens.reqres.setHandler('events:remove:all', function(collection) {
                if(!HoneySens.assureAllowed('events', 'delete')) return false;
                let archived = collection.queryParams.hasOwnProperty('archived') && collection.queryParams.archived,
                    dialog = new ModalEventRemoveView({model: new Backbone.Model({archived: archived, total: collection.state.totalRecords})});
                dialog.listenTo(dialog, 'confirm', function(archive) {
                    module.removeEvents(collection.queryParams, archive, function() {
                        HoneySens.data.models.events.fetch();
                        HoneySens.request('view:modal').empty();
                    });
                });
                HoneySens.request('view:modal').show(dialog);
            });
            HoneySens.reqres.setHandler('events:remove:some', function(selection, fullCollection) {
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
                            HoneySens.request('view:modal').empty();
                        }
                    });
                });
                HoneySens.request('view:modal').show(dialog);
            });
        },
        stop: function() {
            console.log('Stopping module: events');
            HoneySens.reqres.removeHandler('events:show');
            HoneySens.reqres.removeHandler('events:filters:show');
            HoneySens.reqres.removeHandler('events:filters:add');
            HoneySens.reqres.removeHandler('events:filter:edit');
            HoneySens.reqres.removeHandler('events:filter:remove');
        },
        routesList: {
            'events': 'showEvents',
            'events/filters': 'showFilters'
        },
        showEvents: function() {HoneySens.request('events:show');},
        showFilters: function() {HoneySens.request('events:filters:show');},
        exportEvents: function(collection, params) {
            var calcParams = calculateEventQueryParams(params);
            // Sorting
            if(collection.state.sortKey != null) {
                calcParams[collection.queryParams.sortKey] = collection.state.sortKey;
                calcParams[collection.queryParams.order] = collection.queryParams.directions[collection.state.order];
            }
            calcParams.format = 'text/csv';

            console.log(calcParams);

            $.ajax({
                type: 'GET',
                url: 'api/events',
                data: calcParams,
                dataType: 'json',
                success: function(res) {
                    var task = HoneySens.data.models.tasks.add(new Models.Task(res)),
                        awaitTaskView = new ModalAwaitTaskView({model: task});
                    HoneySens.request('view:modal').show(awaitTaskView);
                    HoneySens.Views.waitForTask(task, {
                        done: function(m) {
                            if(!awaitTaskView.isDestroyed) {
                                // Close modal view and start download, then remove the task
                                m.downloadResult(true);
                                awaitTaskView.destroy();
                            }
                        },
                        error: function(m) {
                            if(!awaitTaskView.isDestroyed) {
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

    return HoneySens.module('Events.Routing', EventsModule);
});