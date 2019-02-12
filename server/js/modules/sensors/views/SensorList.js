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
                    this.$el.find('input[type="checkbox"]').attr('disabled', !this.$el.find('input[type="checkbox"]').attr('disabled'));
                }
            },
            onRender: function() {
                var columns = [{
                    name: 'id',
                    label: 'ID',
                    editable: false,
                    cell: Backgrid.IntegerCell.extend({
                        orderSeparator: ''
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
                // Service columns
                //if(HoneySens.assureAllowed('sensors', 'update')) {
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
                                render: function () {
                                    this.$el.html(this.template(this.model.attributes));
                                    // Check if service is set on the model
                                    if (_.contains(_.pluck(this.model.get('services'), 'service'), service.id)) {
                                        this.$el.find('input[type="checkbox"]').prop('checked', true);
                                    }
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
                //}
                // Status and actions columns
                columns.push({
                    label: 'Status',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: SensorListStatusCellTpl,
                        render: function() {
                            // Mix template helpers into template data
                            var templateData = this.model.attributes;
                            templateData.isTimedOut = function() {
                                var now = new Date().getTime() / 1000;
                                // Compare with global update interval in case no individual interval has been set
                                var update_interval = this.update_interval === null ? HoneySens.data.settings.get('sensorsUpdateInterval') : this.update_interval;
                                return (now - this.last_status_ts) > ((update_interval * 60) + 60); // 1 minute timeout tolerance
                            };
                            templateData.showLastStatusTS = function() {
                                var now = new Date().getTime() / 1000,
                                    diffMin = Math.floor((now - this.last_status_ts) / 60);
                                if(diffMin < 60) {
                                    return "vor " + diffMin + " Minuten";
                                } else if(diffMin < (60 * 24)) {
                                    return "vor " + Math.floor(diffMin / 60) + " Stunden";
                                } else if(diffMin >= (60 * 24)) {
                                    return "vor " + Math.floor(diffMin / (60 * 24)) + " Tag(en)";
                                }
                            };
                            // Calculate td classification (for color indication)
                            var className = '',
                                lastStatus = this.model.get('last_status');
                            if(templateData.isTimedOut() || lastStatus == Models.SensorStatus.status.ERROR) {
                                className = 'danger';
                            } else if(lastStatus == Models.SensorStatus.status.UPDATE_PHASE1
                                || lastStatus == Models.SensorStatus.status.INSTALL_PHASE1
                                || lastStatus == Models.SensorStatus.status.UPDATEINSTALL_PHASE2) {
                                className = 'info';
                            } else if(lastStatus == Models.SensorStatus.status.RUNNING) {
                                className = 'success';
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
                            'click button.showStatus': function(e) {
                                e.preventDefault();
                                var collection = this.model.status;
                                collection.fetch({reset: true});
                                HoneySens.request('view:modal').show(new ModalSensorStatusListView({collection: collection}));
                            }
                        },
                        render: function() {
                            this.$el.html(this.template(this.model.attributes));
                            this.$el.find('button').tooltip();
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
                this.$el.find('table.table').css('margin-top', Math.max(this.$el.find('span.serviceLabel').width() - 45, 0));
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