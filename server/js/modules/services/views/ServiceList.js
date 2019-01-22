define(['app/app',
        'backgrid',
        'tpl!app/modules/services/templates/ServiceList.tpl',
        'tpl!app/modules/services/templates/ServiceListActionsCell.tpl'],
function(HoneySens, Backgrid, ServiceListTpl, ServiceListActionsCellTpl) {
    HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ServiceList = Marionette.LayoutView.extend({
            template: ServiceListTpl,
            className: 'row',
            regions: {
                list: 'div.table-responsive'
            },
            events: {
                'click button.add': function(e) {
                    e.preventDefault();
                    HoneySens.request('services:add');
                }
            },
            onRender: function() {
                this.updateRegistryStatus();
                var columns = [{
                    name: 'name',
                    label: 'Name',
                    editable: false,
                    cell: 'string'
                }, {
                    name: 'description',
                    label: 'Beschreibung',
                    editable: false,
                    sortable: false,
                    cell: 'string'
                }, {
                    label: 'Aktionen',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: ServiceListActionsCellTpl,
                        events: {
                            'click button.showDetails': function(e) {
                                e.preventDefault();
                                HoneySens.request('services:details', this.model);
                            },
                            'click button.removeService': function(e) {
                                e.preventDefault();
                                HoneySens.request('services:remove', this.model);
                            }
                        },
                        render: function() {
                            this.$el.html(this.template(this.model.attributes));
                            this.$el.find('button').tooltip();
                            return this;
                        }
                    })
                }];
                var grid = new Backgrid.Grid({
                    columns: columns,
                    collection: this.collection,
                    className: 'table table-striped'
                });
                this.list.show(grid);
                grid.sort('name', 'ascending');
                // Disable all interface controls by default (they are reactivated by the registry status check)
                this.enableInterface(false);
            },
            updateRegistryStatus: function() {
                // Queries registry status and displays the result
                var view = this;
                $.ajax({
                    type: 'GET',
                    url: 'api/services/registry',
                    success: function() {
                        view.$el.find('div.filters span.help-block').removeClass('registryOffline').addClass('registryOnline').text('Online');
                        view.enableInterface(true);
                    },
                    error: function() {
                        view.$el.find('div.filters span.help-block').removeClass('registryOnline').addClass('registryOffline').text('Offline');
                    }
                });
            },
            enableInterface: function (enable) {
                // Enabled or disables all UI controls in this view
                this.$el.find('button').prop('disabled', !enable);
            }
        });
    });

    return HoneySens.Services.Views.ServiceList;
});