define(['app/app',
        'backgrid',
        'tpl!app/modules/platforms/templates/PlatformList.tpl',
        'tpl!app/modules/platforms/templates/PlatformListActionsCell.tpl'],
function(HoneySens, Backgrid, PlatformListTpl, PlatformListActionsCellTpl) {
    HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.PlatformList = Marionette.LayoutView.extend({
            template: PlatformListTpl,
            className: 'row',
            regions: {
                list: 'div.table-responsive'
            },
            events: {
                'click button.add': function(e) {
                    e.preventDefault();
                    HoneySens.request('platforms:firmware:add');
                }
            },
            onRender: function() {
                var columns = [{
                    name: 'title',
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
                        template: PlatformListActionsCellTpl,
                        events: {
                            'click button.showDetails': function(e) {
                                e.preventDefault();
                                HoneySens.request('platforms:details', this.model);
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
                grid.sort('title', 'ascending');
            }
        });
    });

    return HoneySens.Platforms.Views.PlatformList;
});