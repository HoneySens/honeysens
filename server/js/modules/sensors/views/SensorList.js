define(['app/app',
        'app/models',
        'backgrid',
        'app/modules/sensors/views/ModalSensorStatusList',
        'tpl!app/modules/sensors/templates/SensorList.tpl',
        'tpl!app/modules/sensors/templates/SensorListStatusCell.tpl',
        'tpl!app/modules/sensors/templates/SensorListActionsCell.tpl',
        'tpl!app/modules/sensors/templates/SensorListServiceCell.tpl',
        'app/views/common'],
function(HoneySens, Models, Backgrid, ModalSensorStatusListView, SensorListTpl, SensorListStatusCellTpl, SensorListActionsCellTpl, SensorListServiceCellTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.SensorList = Marionette.LayoutView.extend({
            template: SensorListTpl,
            className: 'row',
            actionsDropdownVisibleFor: null,
            servicesEditable: false,
            regions: {
                groupFilter: 'div.groupFilter',
                list: 'div.table-responsive'
            },
            events: {
                'click button.add': function(e) {
                    e.preventDefault();
                    HoneySens.request('sensors:add');
                },
                'click button.toggleServiceEdit': function(e) {
                    e.preventDefault();
                    this.servicesEditable = !this.servicesEditable;
                    this.displayServiceCheckboxes(this.$el);
                    this.$el.find('span.serviceEditLabel').html(this.servicesEditable ? 'sperren' : 'bearbeiten');
                }
            },
            onRender: function() {
                var view = this,
                    columns = [{
                        name: 'id',
                        label: 'ID',
                        editable: false,
                        cell: Backgrid.IntegerCell.extend({
                            orderSeparator: ''
                        })
                    }, {
                        name: 'division',
                        label: 'Gruppe',
                        editable: false,
                        cell: Backgrid.Cell.extend({
                            render: function() {
                                this.$el.html(HoneySens.Views.EventTemplateHelpers.showDivision(this.model));
                                return this;
                            }
                        })
                    }, {
                        name: 'name',
                        label: 'Name',
                        editable: false,
                        cell: 'string'
                    }, {
                        name: 'location',
                        label: 'Standort',
                        editable: false,
                        cell: 'string'
                    }, {
                        label: 'Firmware',
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            render: function() {
                                if(this.model.get('sw_version')) this.$el.html(this.model.get('sw_version'));
                                else this.$el.html('N.A.');
                                return this;
                            }
                        })
                    }, {
                        label: 'IP-Adresse',
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            render: function() {
                                if(this.model.get('last_ip')) this.$el.html(this.model.get('last_ip'));
                                else this.$el.html('N.A.');
                                return this;
                            }
                        })
                    }];
                columns.push({
                    name: 'new_events',
                    label: 'NE',
                    editable: false,
                    sortable: true,
                    cell: Backgrid.IntegerCell.extend({
                        orderSeparator: '',
                        render: function() {
                            var events = this.model.get('new_events');
                            // td classification (for color indication)
                            if(events > 0) this.$el.addClass('warning');
                            this.$el.html(events);
                            return this;
                        }
                    }),
                    headerCell: Backgrid.HeaderCell.extend({
                        render: function () {
                            Backgrid.HeaderCell.prototype.render.apply(this);
                            // Add tooltip
                            var $anchor = this.$el.find('a');
                            $anchor.attr('data-toggle', 'tooltip');
                            $anchor.attr('title', 'Neue Ereignisse');
                            $anchor.tooltip();
                            return this;
                        }
                    })
                });
                // Service columns
                HoneySens.data.models.services.forEach(function (service) {
                    columns.push({
                        name: service.id,
                        label: service.get('name'),
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            template: SensorListServiceCellTpl,
                            events: {
                                'change input[type="checkbox"]': function (e) {
                                    e.preventDefault();
                                    var services = this.model.get('services');
                                    if ($(e.target).is(':checked')) {
                                        if (!_.contains(_.pluck(services, 'service'), service.id)) services.push({
                                            service: service.id,
                                            revision: null
                                        });
                                    } else {
                                        services = _.without(services, _.find(services, function (s) {
                                            return s.service == service.id
                                        }));
                                    }
                                    this.model.save({services: services}, {wait: true});
                                }
                            },
                            initialize: function() {
                                // Re-render this cell on model changes (caused by toggling a service checkbox)
                                this.listenTo(this.model, 'change', function() {
                                    this.render();
                                });
                            },
                            render: function () {
                                this.$el.html(this.template(this.model.attributes));
                                // Check if service is set on the model
                                var serviceActive = _.contains(_.pluck(this.model.get('services'), 'service'), service.id);
                                if (serviceActive) this.$el.find('input[type="checkbox"]').prop('checked', true);
                                // Show/hide checkbox depending on editing mode
                                view.displayServiceCheckboxes(this.$el);
                                // Dye the cell background depending on service status and show an indicator,
                                // but only if the sensor is online and the service is marked as active
                                var lastServiceStatus = this.model.get('last_service_status');
                                if(serviceActive && this.model.get('last_status') && this.model.get('last_status') !== Models.SensorStatus.status.TIMEOUT) {
                                    if (_.has(lastServiceStatus, service.id)) {
                                        // Current service status data is available
                                        switch (lastServiceStatus[service.id]) {
                                            case 0:
                                                this.$el.removeClass('info danger').addClass('success');
                                                this.$el.find('span.statusScheduled, span.statusError').addClass('hide');
                                                this.$el.find('span.statusSuccess').removeClass('hide');
                                                break;
                                            case 1:
                                                this.$el.removeClass('success danger').addClass('info');
                                                this.$el.find('span.statusSuccess, span.statusError').addClass('hide');
                                                this.$el.find('span.statusScheduled').removeClass('hide');
                                                break;
                                            case 2:
                                                this.$el.removeClass('success info').addClass('danger');
                                                this.$el.find('span.statusSuccess, span.statusScheduled').addClass('hide');
                                                this.$el.find('span.statusError').removeClass('hide');
                                                break;
                                        }
                                    } else {
                                        // Sensor isn't aware of the service yet - it's scheduled
                                        this.$el.removeClass('success danger').addClass('info');
                                        this.$el.find('span.statusSuccess, span.statusError').addClass('hide');
                                        this.$el.find('span.statusScheduled').removeClass('hide');
                                    }
                                } else {
                                    this.$el.removeClass('success info danger');
                                    this.$el.find('span.statusSuccess, span.statusScheduled, span.statusError').addClass('hide');
                                }
                                // Enable help popovers
                                this.$el.find('[data-toggle="popover"]').popover();
                                return this;
                            }
                        }),
                        headerCell: Backgrid.HeaderCell.extend({
                            className: 'rotated',
                            render: function () {
                                Backgrid.HeaderCell.prototype.render.apply(this);
                                this.$el.wrapInner('<div><span class="serviceLabel"></span></div>');
                                return this;
                            }
                        })
                    });
                });
                // Status and actions columns
                columns.push({
                    label: 'Status',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: SensorListStatusCellTpl,
                        initialize: function() {
                            // Refresh the view after model updates to recalculate status cell timers
                            this.listenTo(HoneySens.vent, 'models:updated', function() {
                                this.render();
                            });
                        },
                        render: function() {
                            // Mix template helpers into template data
                            var templateData = this.model.attributes;
                            templateData.showLastStatusTS = function() {
                                var timeRef = this.last_status === Models.SensorStatus.status.TIMEOUT ? this.last_status_ts : this.last_status_since;
                                var now = new Date().getTime() / 1000,
                                    diffMin = Math.floor((now - timeRef) / 60);
                                if(diffMin < 60) {
                                    return "seit " + diffMin + " Minuten";
                                } else if(diffMin < (60 * 24)) {
                                    return "seit " + Math.floor(diffMin / 60) + " Stunden";
                                } else if(diffMin >= (60 * 24)) {
                                    return "seit " + Math.floor(diffMin / (60 * 24)) + " Tag(en)";
                                }
                            };
                            // Calculate td classification (for color indication)
                            var className = '';
                            switch(this.model.get('last_status')) {
                                case Models.SensorStatus.status.RUNNING:
                                    className = 'success';
                                    break;
                                case Models.SensorStatus.status.UPDATING:
                                    className = 'info';
                                    break;
                                default:
                                    className = 'danger';
                            }
                            this.$el.addClass(className);
                            // Render template
                            this.$el.html(this.template(templateData));
                            return this;
                        }
                    })
                });
                columns.push({
                    label: 'Aktionen',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: SensorListActionsCellTpl,
                        events: {
                            'click button.removeSensor': function(e) {
                                e.preventDefault();
                                HoneySens.request('sensors:remove', this.model);
                            },
                            'click button.editSensor': function(e) {
                                e.preventDefault();
                                HoneySens.request('sensors:edit', this.model);
                            },
                            'show.bs.dropdown div.dropdown': function(e) {
                                view.actionsDropdownVisibleFor = this.model.id;
                            },
                            'hide.bs.dropdown div.dropdown': function(e) {
                                view.actionsDropdownVisibleFor = null;
                            },
                            'click a.showStatus': function(e) {
                                e.preventDefault();
                                var collection = this.model.status;
                                collection.fetch({reset: true});
                                HoneySens.request('view:modal').show(new ModalSensorStatusListView({collection: collection}));
                            },
                            'click a.downloadConfig': function(e) {
                                e.preventDefault();
                                HoneySens.request('sensors:config:download', this.model);
                            }
                        },
                        render: function() {
                            this.$el.html(this.template(this.model.attributes));
                            this.$el.find('button').tooltip();
                            if(view.actionsDropdownVisibleFor === this.model.id) {
                                // Redraw dropdown in case it was previously visible
                                this.$el.find('div.dropdown').addClass('open');
                            }
                            return this;
                        }
                    })
                });
                var grid = new Backgrid.Grid({
                    columns: columns,
                    collection: this.collection,
                    className: 'table table-striped rotated'
                });
                this.list.show(grid);
                grid.sort('id', 'ascending');
                // Division filter
                var divisions = _.union([{label: 'Alle', value: null}],
                    HoneySens.data.models.divisions.map(function(division) {
                        return {label: division.get('name'), value: division.id};
                    })
                );
                this.groupFilterView = new Backgrid.Extension.SelectFilter({
                    className: 'backgrid-filter form-control',
                    collection: this.collection,
                    field: 'division',
                    selectOptions: divisions
                });
                this.groupFilter.show(this.groupFilterView);
            },
            onShow: function() {
                // Readjust table margin so that all service labels are visible
                var $serviceLabels = this.$el.find('span.serviceLabel');
                this.$el.find('table.table').css('margin-top', Math.max(...$.map($serviceLabels, (e) => $(e).outerWidth() - 45), 0));
            },
            displayServiceCheckboxes: function($anchor) {
                if(this.servicesEditable) {
                    $anchor.find('div.statusIndicator').addClass('hide');
                    $anchor.find('input[type="checkbox"]').removeClass('hide');
                }
                else {
                    $anchor.find('div.statusIndicator').removeClass('hide');
                    $anchor.find('input[type="checkbox"]').addClass('hide');
                }
            },
            templateHelpers: {
                hasDivision: function() {
                    // checks whether there is at least one division available
                    return HoneySens.data.models.divisions.length > 0;
                }
            }
        });
    });

    return HoneySens.Sensors.Views.SensorList;
});
