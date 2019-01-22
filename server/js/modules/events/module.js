define(['app/app', 'app/routing', 'app/models',
        'app/modules/events/views/Layout',
        'app/modules/events/views/EventList',
        'app/modules/events/views/EventEdit',
        'app/modules/events/views/FilterList',
        'app/modules/events/views/FilterEdit',
        'app/modules/events/views/ModalFilterRemove'],
function(HoneySens, Routing, Models, LayoutView, EventListView, EventEditView, FilterListView, FilterEditView, ModalFilterRemoveView) {
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
        showFilters: function() {HoneySens.request('events:filters:show');}
    });

    return HoneySens.module('Events.Routing', EventsModule);
});