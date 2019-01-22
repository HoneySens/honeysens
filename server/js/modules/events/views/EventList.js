define(['app/app',
        'app/models',
        'backgrid',
        'app/modules/events/views/EventDetails',
        'app/modules/events/views/ModalEventRemove',
        'tpl!app/modules/events/templates/EventList.tpl',
        'tpl!app/modules/events/templates/EventListStatusCell.tpl',
        'tpl!app/modules/events/templates/EventListActionsCell.tpl',
        'backgrid-paginator',
        'backgrid-select-filter',
        'backgrid-select-all',
        'app/views/common'],
function(HoneySens, Models, Backgrid, EventDetailsView, ModalEventRemoveView, EventListTpl, EventListStatusCellTpl, EventListActionsCellTpl) {
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
                paginator: 'div.paginator'
            },
            events: {
                'click button.massEdit': function(e) {
                    e.preventDefault();
                    HoneySens.request('events:edit', new Models.Events(this.grid.getSelectedModels()));
                },
                'click button.massDelete': function(e) {
                    e.preventDefault();
                    var models = new Models.Events(this.grid.getSelectedModels());
                    var dialog = new ModalEventRemoveView({collection: models});
                    HoneySens.request('view:modal').show(dialog);
                }
            },
            onRender: function() {
                var view = this;
                // Adjust page size on viewport changes
                $(window).resize(function() {
                    view.refreshPageSize(view.collection);
                });
                // Reset collection (in case some queryParams were set previously)
                delete HoneySens.data.models.events.queryParams.classification;
                delete HoneySens.data.models.events.queryParams.sensor;
                delete HoneySens.data.models.events.queryParams.division;

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
                        sortable: false,
                        cell: Backgrid.IntegerCell.extend({
                            orderSeparator: ''
                        })
                    }, {
                        name: 'timestamp',
                        label: 'Zeitpunkt',
                        editable: false,
                        sortType: 'toggle',
                        cell: Backgrid.Cell.extend({
                            render: function() {
                                this.$el.html(HoneySens.Views.EventTemplateHelpers.showTimestamp(this.model.get('timestamp')));
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
                                this.$el.html(HoneySens.Views.EventTemplateHelpers.showSensor(this.model.get('sensor')));
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
                        sortable: false,
                        cell: 'string'
                    }, {
                        label: 'Status',
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            template: EventListStatusCellTpl,
                            events: {
                                'mouseenter button.editStatus': function(e) {
                                    e.preventDefault();
                                    this.$el.find('button.editStatus').popover('show');
                                },
                                'mouseleave': function(e) {
                                    e.preventDefault();
                                    if(e.target.tagName.toLowerCase() != 'select') {
                                        this.$el.find('button.editStatus').popover('hide');
                                    }
                                },
                                'click button.btn-primary': function(e) {
                                    e.preventDefault();
                                    var statusCode = this.$el.find('div.popover.fade select.statusCode').val(),
                                        comment = this.$el.find('div.popover.fade textarea').val(),
                                        view = this;
                                    this.model.save({status: statusCode, comment: comment}, {wait: true, success: function() {
                                        view.$el.find('button.editStatus').popover('hide');
                                    }});
                                    this.$el.find('div.popover.fade button.btn-primary').prop('disabled', true);
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
                                return this
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
                                    var dialog = new ModalEventRemoveView({model: this.model});
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
                        // Highlight newly added rows
                        if(this.model.get('new')) {
                            var $itemView = this.$el;
                            $itemView.addClass('info');
                            setTimeout(function() {
                                $itemView.removeClass('info');
                            }, 1000);
                            //this.model.set('new', false);
                        }
                        // Render row color depending upon the event classification
                        switch(this.model.get('classification')) {
                            case Models.Event.classification.LOW_HP:
                                if(this.$el.hasClass('info')) {
                                    var $itemView = this.$el;
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
                this.grid.sort('timestamp', 'descending');
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
            },
            onShow: function() {
                this.refreshPageSize(this.collection);
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