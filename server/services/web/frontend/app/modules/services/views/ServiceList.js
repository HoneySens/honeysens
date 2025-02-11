import HoneySens from 'app/app';
import Backgrid from 'backgrid';
import ServiceListTpl from 'app/modules/services/templates/ServiceList.tpl';
import ServiceListActionsCellTpl from 'app/modules/services/templates/ServiceListActionsCell.tpl';

HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ServiceList = Marionette.LayoutView.extend({
        template: _.template(ServiceListTpl),
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
                label: _.t('name'),
                editable: false,
                cell: 'string'
            }, {
                name: 'description',
                label: _.t('services:serviceDescription'),
                editable: false,
                sortable: false,
                cell: 'string'
            }, {
                label: _.t('actions'),
                editable: false,
                sortable: false,
                cell: Backgrid.Cell.extend({
                    template: _.template(ServiceListActionsCellTpl),
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
                dataType: 'json',
                url: 'api/services/status',
                success: function(res) {
                    let $status = view.$el.find('div.filters span.help-block');
                    if(res.registry) {
                        if(res.services) $status.addClass('statusOnline').text(_.t('services:serviceStatusOnline'));
                        else $status.addClass('statusWarning').text(_.t('services:serviceStatusWarning'));
                        view.enableInterface(true);
                    } else $status.addClass('statusOffline').text(_.t('services:serviceStatusOffline'));
                },
                error: function() {
                    view.$el.find('div.filters span.help-block').addClass('statusOffline').text(_.t('services:noServerConnection'));
                }
            });
        },
        enableInterface: function (enable) {
            // Enables or disables all UI controls in this view
            this.$el.find('button').prop('disabled', !enable);
        }
    });
});

export default HoneySens.Services.Views.ServiceList;