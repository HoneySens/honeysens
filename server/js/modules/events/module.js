define(['app/app', 'app/routing', 'app/models',
        'app/modules/events/views/Layout',
        'app/modules/events/views/EventList',
        'app/modules/events/views/EventEdit',
        'app/modules/events/views/FilterList',
        'app/modules/events/views/FilterEdit',
        'app/modules/events/views/ModalFilterRemove',
        'app/modules/tasks/views/ModalAwaitTask'],
function(HoneySens, Routing, Models, LayoutView, EventListView, EventEditView, FilterListView, FilterEditView, ModalFilterRemoveView, ModalAwaitTaskView) {
    var EventsModule = Routing.extend({
        name: 'events',
        startWithParent: false,
        rootView: null,
        menuItems: [
            {title: 'Ereignisse', uri: 'events', iconClass: 'glyphicon glyphicon-list', permission: {domain: 'events', action: 'get'}, priority: 1},
            {title: 'Filter', uri: 'events/filters', iconClass: 'glyphicon glyphicon-filter', permission: {domain: 'eventfilters', action: 'create'}}
        ],
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
            HoneySens.reqres.setHandler('events:edit', function(models) {
                if(!HoneySens.assureAllowed('events', 'update')) return false;
                HoneySens.request('view:content').overlay.show(new EventEditView({collection: models}));
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
            HoneySens.reqres.setHandler('events:filters:edit', function(filter) {
                if(!HoneySens.assureAllowed('eventfilters', 'update')) return false;
                HoneySens.request('view:content').overlay.show(new FilterEditView({model: filter}));
            });
            HoneySens.reqres.setHandler('events:filters:remove', function(filter) {
                HoneySens.request('view:modal').show(new ModalFilterRemoveView({model: filter}));
            });
            HoneySens.reqres.setHandler('events:export:all', function(collection) {
                module.exportEvents(collection, _.clone(collection.queryParams));
            });
            HoneySens.reqres.setHandler('events:export:page', function(collection) {
                // Export currently displayed page, that is all events currently within the collection
                var params = _.clone(collection.queryParams);
                params.list = collection.pluck('id');
                module.exportEvents(collection, _.clone(params));
            });
            HoneySens.reqres.setHandler('events:export:list', function(collection, events) {
                // Pluck query params from the collection, but use the event collection as actual event list
                var params = _.clone(collection.queryParams);
                params.list = events.pluck('id');
                module.exportEvents(collection, _.clone(params));
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
            // Clean up and assemble parameters
            delete params.currentPage;
            delete params.pageSize;
            delete params.totalPages;
            delete params.totalRecords;
            delete params.sortKey;
            delete params.order;
            delete params.directions;
            // Assign the return values of function params
            _.each(_.clone(params), function(param, key) {
                if(_.isFunction(param)) params[key] = param();
            });
            // Sorting
            if(collection.state.sortKey != null) {
                params[collection.queryParams.sortKey] = collection.state.sortKey;
                params[collection.queryParams.order] = collection.queryParams.directions[collection.state.order];
            }
            params.format = 'text/csv';
            $.ajax({
                type: 'GET',
                url: 'api/events',
                data: params,
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
        }
    });

    return HoneySens.module('Events.Routing', EventsModule);
});