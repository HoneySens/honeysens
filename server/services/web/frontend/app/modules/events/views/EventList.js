import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import { EventClassification, EventStatus, Events } from 'app/models';
import Backgrid from 'backgrid';
import EventDetailsView from 'app/modules/events/views/EventDetails';
import ModalEventRemoveView from 'app/modules/events/views/ModalEventRemove';
import BackgridDatepickerFilter from 'app/common/views/BackgridDatepickerFilter';
import EventListTpl from 'app/modules/events/templates/EventList.tpl';
import EventListStatusCellTpl from 'app/modules/events/templates/EventListStatusCell.tpl';
import EventListActionsCellTpl from 'app/modules/events/templates/EventListActionsCell.tpl';
import 'backgrid-paginator';
import 'backgrid-select-filter';
import 'backgrid-select-all';
import 'backgrid-filter';
import { EventTemplateHelpers } from 'app/views/common';

function getSensorSelectOptions() {
    var division = parseInt($('div.groupFilter select').val()),
        sensors = HoneySens.data.models.sensors;
    if(division >= 0) sensors = sensors.where({division: division});
    else sensors = sensors.models;
    return _.union([{label: i18n.t('all'), value: null}],
        _.map(sensors, function(sensor) {
            return {label: sensor.get('name'), value: sensor.id};
        })
    );
}

const EventList = View.extend({
    template: _.template(EventListTpl),
    templateContext: {...i18n},
    grid: null,
    regions: {
        groupFilter: 'div.groupFilter',
        sensorFilter: 'div.sensorFilter',
        classificationFilter: 'div.classificationFilter',
        list: 'div.table-responsive',
        paginator: 'div.paginator',
        eventFilter: 'div.eventFilter',
        statusFilter: 'div.statusFilter',
        dateFilter: 'div.dateFilter',
        sourceFilter: 'div.sourceFilter'
    },
    events: {
        'click button.massExport': function() {
            radio.request('events:export:list', this.collection, new Events(this.grid.getSelectedModels()));
        },
        'click button.massEdit': function() {
            radio.request('events:edit:some', new Events(this.grid.getSelectedModels()));
        },
        'click button.massDelete': function() {
            radio.request('events:remove:some', new Events(this.grid.getSelectedModels()), this.collection);
        },
        'click a.exportPage': function() {
            radio.request('events:export:page', this.collection);
        },
        'click a.exportAll': function() {
            radio.request('events:export:all', this.collection);
        },
        'click a.editPage': function() {
            radio.request('events:edit:some', this.collection);
        },
        'click a.editAll': function() {
            radio.request('events:edit:all', this.collection);
        },
        'click a.removePage': function() {
            radio.request('events:remove:some', this.collection, this.collection);
        },
        'click a.removeAll': function() {
            radio.request('events:remove:all', this.collection);
        }
    },
    onRender: function() {
        var view = this;
        // Adjust page size on viewport changes
        $(window).resize(function() {
            view.refreshPageSize(view.collection);
        });
        // Reset query params (in case they were set previously), status is set via its filters' initialValue
        delete HoneySens.data.models.events.queryParams.classification;
        delete HoneySens.data.models.events.queryParams.sensor;
        delete HoneySens.data.models.events.queryParams.division;
        delete HoneySens.data.models.events.queryParams.archived;
        delete HoneySens.data.models.events.queryParams.filter;
        this.collection.state.order = 1;
        this.collection.state.sortKey = 'timestamp';


        var columns = [{
            name: '',
            cell: 'select-row',
            headerCell: 'select-all',
            editable: false,
            sortable: false
        }, {
            name: 'id',
            label: i18n.t('id'),
            editable: false,
            sortType: 'toggle',
            cell: Backgrid.Cell.extend({
                render: function() {
                    this.$el.html(this.model.get('archived') ? this.model.get('oid') : this.model.id);
                    return this;
                }
            })
        }, {
            name: 'timestamp',
            label: i18n.t('timestamp'),
            editable: false,
            sortType: 'toggle',
            direction: 'descending',
            cell: Backgrid.Cell.extend({
                render: function() {
                    this.$el.html(EventTemplateHelpers.showTimestamp(this.model.get('timestamp')));
                    return this;
                }
            })
        }, {
            name: 'division',
            label: i18n.t('division'),
            editable: false,
            sortType: 'toggle',
            cell: Backgrid.Cell.extend({
                render: function() {
                    this.$el.html(EventTemplateHelpers.showDivisionForEvent(this.model.attributes));
                    return this;
                }
            })
        }, {
            name: 'sensor',
            label: i18n.t('sensor'),
            editable: false,
            sortType: 'toggle',
            cell: Backgrid.Cell.extend({
                render: function() {
                    this.$el.html(EventTemplateHelpers.showSensor(this.model.attributes));
                    return this;
                }
            })
        }, {
            name: 'classification',
            label: i18n.t('events:eventClassification'),
            editable: false,
            sortType: 'toggle',
            cell: Backgrid.Cell.extend({
                render: function() {
                    this.$el.html(EventTemplateHelpers.showClassification(this.model.get('classification')));
                    return this;
                }
            })
        }, {
            name: 'source',
            label: i18n.t('events:eventSource'),
            editable: false,
            sortType: 'toggle',
            cell: 'string'
        }, {
            name: 'summary',
            label: i18n.t('details'),
            editable: false,
            sortType: 'toggle',
            cell: Backgrid.Cell.extend({
                render: function() {
                    var summary = this.model.get('summary');
                    // The Recon service writes 'Einzelverbindung' into the summary field, translate it
                    if(summary === 'Einzelverbindung') summary = i18n.t('events:eventDetailRecon');
                    this.$el.html(EventTemplateHelpers.showSummary(
                        summary,
                        this.model.get('numberOfPackets'),
                        this.model.get('numberOfDetails')
                    ));
                    return this;
                }
            })
        }, {
            name: 'status',
            label: i18n.t('events:eventStatus'),
            editable: false,
            sortType: 'toggle',
            cell: Backgrid.Cell.extend({
                template: _.template(EventListStatusCellTpl),
                events: {
                    'mouseenter button.editStatus': function(e) {
                        e.preventDefault();
                        this.$el.find('button.editStatus').popover('show');
                    },
                    'mouseleave': function(e) {
                        e.preventDefault();
                        this.$el.find('button.editStatus').popover('hide');
                    },
                    'click button.editStatus': function(e) {
                        e.preventDefault();
                        radio.request('events:edit:single', this.model);
                        this.$el.find('button.editStatus').popover('hide');
                    }
                },
                render: function() {
                    this.$el.html(this.template(_.extend({t: i18n.t, EventStatus: EventStatus}, this.model.attributes)));
                    // initialize popover for editing
                    this.$el.find('button.editStatus').popover({
                        html: true,
                        content: function() {
                            return $(this).siblings('div.popover').find('div.popover-content').html();
                        },
                        placement: 'left',
                        trigger: 'manual',
                        container: this.$el.find('button.editStatus').parent()
                    });
                    // subscribe to model 'change' event
                    this.listenTo(this.model, 'change', function() {
                        this.render();
                    });
                    return this;
                }
            })
        }, {
            label: i18n.t('actions'),
            editable: false,
            sortable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(EventListActionsCellTpl),
                events: {
                    'click button.showEvent': function(e) {
                        e.preventDefault();
                        radio.request('view:content').getRegion('overlay').show(new EventDetailsView({model: this.model}));
                    },
                    'click button.removeEvent': function(e) {
                        e.preventDefault();
                        let dialog = new ModalEventRemoveView({model: this.model});
                        this.listenTo(dialog, 'confirm', function(archive) {
                            $.ajax({
                                type: 'DELETE',
                                url: 'api/events',
                                data: JSON.stringify({id: this.model.id, archived: this.model.get('archived'), archive: archive}),
                                contentType: 'application/json',
                                success: function() {
                                    HoneySens.data.models.events.fetch();
                                    radio.request('view:modal').empty();
                                }
                            });
                        });
                        radio.request('view:modal').show(dialog);
                    }
                },
                render: function() {
                    this.$el.html(this.template(_.extend({t: i18n.t}, this.model.attributes)));
                    this.$el.find('button').tooltip();
                    return this;
                }
            })
        }];
        var row = Backgrid.Row.extend({
            render: function() {
                Backgrid.Row.prototype.render.call(this);
                // In case the currently rendered row is new, highlight it
                if(HoneySens.data.models.new_events.get(this.model.id)) {
                    let $itemView = this.$el,
                        newModelId = this.model.id;
                    $itemView.addClass('info');
                    setTimeout(function() {
                        $itemView.removeClass('info');
                        HoneySens.data.models.new_events.remove(newModelId);
                    }, 1000);
                }
                // Render row color depending on event classification
                switch(this.model.get('classification')) {
                    case EventClassification.LOW_HP:
                        if(this.$el.hasClass('info')) {
                            let $itemView = this.$el;
                            setTimeout(function() {
                                $itemView.addClass('danger');
                            }, 1000);
                        } else this.$el.addClass('danger');
                        break;
                    case EventClassification.PORTSCAN:
                        if(this.$el.hasClass('info')) {
                            var $itemView = this.$el;
                            setTimeout(function() {
                                $itemView.addClass('warning');
                            }, 1000);
                        } else this.$el.addClass('warning');
                        break;
                }
                return this;
            }
        });
        this.grid = new Backgrid.Grid({
            row: row,
            columns: columns,
            collection: this.collection,
            className: 'table table-striped'
        });
        var paginator = new Backgrid.Extension.Paginator({
            collection: this.collection,
            goBackFirstOnSort: false
        });
        this.getRegion('list').show(this.grid);
        this.getRegion('paginator').show(paginator);
        // Division filter
        var divisions = _.union([{label: i18n.t('allDivisions'), value: null}],
            HoneySens.data.models.divisions.map(function(division) {
                return {label: division.get('name'), value: division.id};
            })
        );
        var GroupFilterView = Backgrid.Extension.SelectFilter.extend({
            onChange: function() {
                // Reset the sensor filter
                view.sensorFilterView.selectOptions = getSensorSelectOptions();
                view.sensorFilterView.render();
                delete HoneySens.data.models.events.queryParams.sensor;
                Backgrid.Extension.SelectFilter.prototype.onChange.call(this);
            }
        });
        this.groupFilterView = new GroupFilterView({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'division',
            selectOptions: divisions
        });
        this.getRegion('groupFilter').show(this.groupFilterView);
        // Sensor filter
        this.sensorFilterView = new Backgrid.Extension.SelectFilter({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'sensor',
            selectOptions: getSensorSelectOptions()
        });
        this.getRegion('sensorFilter').show(this.sensorFilterView);
        // Event control box tooltips
        this.$el.find('div.selectionOptions button').tooltip();
        // Classification filter
        this.classificationFilterView = new Backgrid.Extension.SelectFilter({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'classification',
            selectOptions: [
                {label: i18n.t('all'), value: null},
                {label: i18n.t('eventClassificationConnectionAttempt'), value: '2'},
                {label: i18n.t('eventClassificationScan'), value: 4},
                {label: i18n.t('eventClassificationHoneypot'), value: '3'}
            ]
        });
        this.getRegion('classificationFilter').show(this.classificationFilterView);
        // Status filter
        this.statusFilterView = new Backgrid.Extension.SelectFilter({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'status',
            initialValue: '0,1',
            selectOptions: [
                {label: i18n.t('events:eventListFilterStatusUneditedBusy'), value: '0,1'},
                {label: i18n.t('events:eventListFilterStatusResolvedIgnored'), value: '2,3'},
                {label: i18n.t('events:eventStatusUnedited'), value: '0'},
                {label: i18n.t('events:eventStatusBusy'), value: '1'},
                {label: i18n.t('events:eventStatusResolved'), value: '2'},
                {label: i18n.t('events:eventStatusIgnored'), value: '3'},
                {label: i18n.t('all'), value: null}
            ]
        });
        this.getRegion('statusFilter').show(this.statusFilterView);
        // Date filter
        this.dateFilterView = new BackgridDatepickerFilter({
            collection: this.collection,
            fromField: 'fromTS',
            toField: 'toTS'
        });
        this.getRegion('dateFilter').show(this.dateFilterView);
        // Search box
        var eventFilter = new Backgrid.Extension.ServerSideFilter({
            template: function(data) {
                return '<span class="search">&nbsp;</span><input style="width: 25em;" class="form-control" type="search" ' + (data.placeholder ? 'placeholder="' + data.placeholder + '"' : '') + ' name="' + data.name + '" ' + (data.value ? 'value="' + data.value + '"' : '') + '/><a class="clear" data-backgrid-action="clear" href="#">&times;</a>';
            },
            collection: this.collection,
            name: 'filter',
            placeholder: i18n.t('events:eventListFilterSearchPlaceholder')
        });
        this.getRegion('eventFilter').show(eventFilter);
        // Source filter
        this.sourceFilterView = new Backgrid.Extension.SelectFilter({
            className: 'backgrid-filter form-control',
            collection: this.collection,
            field: 'archived',
            selectOptions: [
                {label: i18n.t('events:eventListFilterDatasetLive'), value: false},
                {label: i18n.t('events:eventListFilterDatasetArchive'), value: true}
            ],
            beforeChange: function(e) {
                let switchingToArchive = e.target.value === 'true',
                    $sensorFilter = view.$el.find('div.sensorFilter select');
                if(switchingToArchive) {
                    // Reset both sensor (not filterable) and status filter (to show all archived events)
                    delete HoneySens.data.models.events.queryParams.sensor;
                    delete HoneySens.data.models.events.queryParams.status;
                    view.$el.find('div.statusFilter select').val('null');
                    $sensorFilter.val('null');
                } else {
                    // When switching back to live view, only show new and busy events
                    HoneySens.data.models.events.queryParams.status = '0,1';
                    view.$el.find('div.statusFilter select').val('"0,1"');
                }
                // Enable/disable sensor filter
                $sensorFilter.prop('disabled', switchingToArchive);
                // Edit buttons
                view.$el.find('button.massEdit').prop('disabled', switchingToArchive);
                view.$el.find('button.massDelete').prop('disabled', !(_.templateHelpers.isAllowed('events', 'delete')
                    || (!switchingToArchive && _.templateHelpers.isAllowed('events', 'archive'))));
                let $groupEditElements = view.$el.find('.groupEditElement');
                if(switchingToArchive) $groupEditElements.addClass('hidden')
                else $groupEditElements.removeClass('hidden');
            }
        });
        this.getRegion('sourceFilter').show(this.sourceFilterView);
        // Display control box when models are selected and update counter
        this.listenTo(this.collection, 'backgrid:selected', function() {
            view.updateSelectionControlPanel()
        });
        this.listenTo(this.collection, 'destroy', function() {
            view.updateSelectionControlPanel()
        });
        // Clear model selection on pagination state changes (also prevents a backgrid-select-all bug with server-side collections)
        this.listenTo(this.collection, 'pageable:state:change', function() {
            view.grid.clearSelectedModels();
        });
        this.listenTo(this.collection, 'update', function() {
            this.refreshPageSize(this.collection);
        });
        // Update event collection when new events are announced
        this.listenTo(radio, 'models:events:new', function(ids) {
            this.collection.fetch({
                success: function(collection) {
                    // Force rendering of potential new rows
                    collection.trigger('reset', collection, {});
                }
            });
        });
    },
    onDestroy: function() {
        HoneySens.data.models.events.reset();
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
    updateSelectionControlPanel: function() {
        var $selectOptions = this.$el.find('div.selectionOptions'),
            selectionCount = this.grid.getSelectedModels().length;
        if(selectionCount > 0) $selectOptions.removeClass('hidden');
        else $selectOptions.addClass('hidden');
        this.$el.find('span.selectionCounter').text(selectionCount);
    }
});

export default EventList;
