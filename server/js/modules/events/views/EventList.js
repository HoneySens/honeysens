define(['app/app',
        'app/models',
        'backgrid',
        'app/modules/events/views/EventDetails',
        'app/modules/events/views/ModalEventRemove',
        'app/common/views/BackgridDatepickerFilter',
        'tpl!app/modules/events/templates/EventList.tpl',
        'tpl!app/modules/events/templates/EventListStatusCell.tpl',
        'tpl!app/modules/events/templates/EventListActionsCell.tpl',
        'backgrid-paginator',
        'backgrid-select-filter',
        'backgrid-select-all',
        'backgrid-filter',
        'app/views/common'],
function(HoneySens, Models, Backgrid, EventDetailsView, ModalEventRemoveView, BackgridDatepickerFilter, EventListTpl, EventListStatusCellTpl, EventListActionsCellTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        function getSensorSelectOptions() {
            var division = parseInt($('div.groupFilter select').val()),
                sensors = HoneySens.data.models.sensors;
            if(division >= 0) sensors = sensors.where({division: division});
            else sensors = sensors.models;
            return _.union([{label: 'Alle', value: null}],
                _.map(sensors, function(sensor) {
                    return {label: sensor.get('name'), value: sensor.id};
                })
            );
        }

        Views.EventList  = Marionette.LayoutView.extend({
            template: EventListTpl,
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
                    HoneySens.request('events:export:list', this.collection, new Models.Events(this.grid.getSelectedModels()));
                },
                'click button.massEdit': function() {
                    HoneySens.request('events:edit:some', new Models.Events(this.grid.getSelectedModels()));
                },
                'click button.massDelete': function() {
                    HoneySens.request('events:remove:some', new Models.Events(this.grid.getSelectedModels()), this.collection);
                },
                'click a.exportPage': function() {
                    HoneySens.request('events:export:page', this.collection);
                },
                'click a.exportAll': function() {
                    HoneySens.request('events:export:all', this.collection);
                },
                'click a.editPage': function() {
                    HoneySens.request('events:edit:some', this.collection);
                },
                'click a.editAll': function() {
                    HoneySens.request('events:edit:all', this.collection);
                },
                'click a.removePage': function() {
                    HoneySens.request('events:remove:some', this.collection, this.collection);
                },
                'click a.removeAll': function() {
                    HoneySens.request('events:remove:all', this.collection);
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
                    label: 'ID',
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
                    label: 'Zeitpunkt',
                    editable: false,
                    sortType: 'toggle',
                    direction: 'descending',
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(HoneySens.Views.EventTemplateHelpers.showTimestamp(this.model.get('timestamp')));
                            return this;
                        }
                    })
                }, {
                    name: 'division',
                    label: 'Gruppe',
                    editable: false,
                    sortType: 'toggle',
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(HoneySens.Views.EventTemplateHelpers.showDivisionForEvent(this.model.attributes));
                            return this;
                        }
                    })
                }, {
                    name: 'sensor',
                    label: 'Sensor',
                    editable: false,
                    sortType: 'toggle',
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(HoneySens.Views.EventTemplateHelpers.showSensor(this.model.attributes));
                            return this;
                        }
                    })
                }, {
                    name: 'classification',
                    label: 'Klassifikation',
                    editable: false,
                    sortType: 'toggle',
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(HoneySens.Views.EventTemplateHelpers.showClassification(this.model.get('classification')));
                            return this;
                        }
                    })
                }, {
                    name: 'source',
                    label: 'Quelle',
                    editable: false,
                    sortType: 'toggle',
                    cell: 'string'
                }, {
                    name: 'summary',
                    label: 'Details',
                    editable: false,
                    sortType: 'toggle',
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            this.$el.html(HoneySens.Views.EventTemplateHelpers.showSummary(
                                this.model.get('summary'),
                                this.model.get('numberOfPackets'),
                                this.model.get('numberOfDetails')
                            ));
                            return this;
                        }
                    })
                }, {
                    name: 'status',
                    label: 'Status',
                    editable: false,
                    sortType: 'toggle',
                    cell: Backgrid.Cell.extend({
                        template: EventListStatusCellTpl,
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
                                HoneySens.request('events:edit:single', this.model);
                                this.$el.find('button.editStatus').popover('hide');
                            }
                        },
                        render: function() {
                            this.$el.html(this.template(this.model.attributes));
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
                    label: 'Aktionen',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: EventListActionsCellTpl,
                        events: {
                            'click button.showEvent': function(e) {
                                e.preventDefault();
                                HoneySens.request('view:content').overlay.show(new EventDetailsView({model: this.model}));
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
                                            HoneySens.request('view:modal').empty();
                                        }
                                    });
                                });
                                HoneySens.request('view:modal').show(dialog);
                            }
                        },
                        render: function() {
                            this.$el.html(this.template(this.model.attributes));
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
                        // Render row color depending upon the event classification
                        switch(this.model.get('classification')) {
                            case Models.Event.classification.LOW_HP:
                                if(this.$el.hasClass('info')) {
                                    let $itemView = this.$el;
                                    setTimeout(function() {
                                        $itemView.addClass('danger');
                                    }, 1000);
                                } else this.$el.addClass('danger');
                                break;
                            case Models.Event.classification.PORTSCAN:
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
                this.list.show(this.grid);
                this.paginator.show(paginator);
                // Division filter
                var divisions = _.union([{label: 'Alle', value: null}],
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
                this.groupFilter.show(this.groupFilterView);
                // Sensor filter
                this.sensorFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'sensor',
                    selectOptions: getSensorSelectOptions()
                });
                this.sensorFilter.show(this.sensorFilterView);
                // Event control box tooltips
                this.$el.find('div.selectionOptions button').tooltip();
                // Classification filter
                this.classificationFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'classification',
                    selectOptions: [
                        {label: 'Alle', value: null},
                        {label: 'Verbindungsversuch', value: '2'},
                        {label: 'Scan', value: 4},
                        {label: 'Honeypot', value: '3'}
                    ]
                });
                this.classificationFilter.show(this.classificationFilterView);
                // Status filter
                this.statusFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'status',
                    initialValue: '0,1',
                    selectOptions: [
                        {label: 'Neu & In Bearb.', value: '0,1'},
                        {label: 'Erledigt & Ign.', value: '2,3'},
                        {label: 'Neu', value: '0'},
                        {label: 'In Bearbeitung', value: '1'},
                        {label: 'Erledigt', value: '2'},
                        {label: 'Ignoriert', value: '3'},
                        {label: 'Alle', value: null}
                    ]
                });
                this.statusFilter.show(this.statusFilterView);
                // Date filter
                this.dateFilterView = new BackgridDatepickerFilter({
                    collection: this.collection,
                    fromField: 'fromTS',
                    toField: 'toTS'
                });
                this.dateFilter.show(this.dateFilterView);
                // Search box
                var eventFilter = new Backgrid.Extension.ServerSideFilter({
                    template: function(data) {
                        return '<span class="search">&nbsp;</span><input style="width: 25em;" class="form-control" type="search" ' + (data.placeholder ? 'placeholder="' + data.placeholder + '"' : '') + ' name="' + data.name + '" ' + (data.value ? 'value="' + data.value + '"' : '') + '/><a class="clear" data-backgrid-action="clear" href="#">&times;</a>';
                    },
                    collection: this.collection,
                    name: "filter",
                    placeholder: "Suche nach Datum, Quelle oder Kommentar"
                });
                this.eventFilter.show(eventFilter);
                // Source filter
                this.sourceFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'archived',
                    selectOptions: [
                        {label: 'Live', value: false},
                        {label: 'Archiv', value: true}
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
                this.sourceFilter.show(this.sourceFilterView);
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
                // Update event collection when new events are announced
                this.listenTo(HoneySens.vent, 'models:events:new', function(ids) {
                    this.collection.fetch({
                        success: function(collection) {
                            // Force rendering of potential new rows
                            collection.trigger('reset', collection, {});
                        }
                    });
                });
            },
            onShow: function() {
                this.refreshPageSize(this.collection);
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
    });

    return HoneySens.Events.Views.EventList;
});
