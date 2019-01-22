define(['app/app',
        'app/models',
        'backgrid',
        'tpl!app/modules/events/templates/FilterList.tpl',
        'tpl!app/modules/events/templates/FilterListActionsCell.tpl',
        'app/views/common',
        'backgrid-select-filter'],
function(HoneySens, Models, Backgrid, FilterListTpl, FilterListActionsCellTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.FilterList = Marionette.LayoutView.extend({
            template: FilterListTpl,
            className: 'row',
            regions: {
                groupFilter: 'div.groupFilter',
                list: 'div.table-responsive'
            },
            events: {
                'click button.add': function(e) {
                    e.preventDefault();
                    HoneySens.request('events:filters:add');
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
                    label: 'Typ',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            switch(this.model.get('type')) {
                                case Models.EventFilter.type.WHITELIST:
                                    this.$el.html('Whitelist');
                            }
                            return this;
                        }
                    })
                }, {
                    name: 'count',
                    label: 'Zähler',
                    editable: false,
                    cell: Backgrid.IntegerCell.extend({
                        orderSeparator: ''
                    })
                }, {
                    label: 'Aktionen',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: FilterListActionsCellTpl,
                        events: {
                            'click button.edit': function(e) {
                                e.preventDefault();
                                HoneySens.request('events:filters:edit', this.model);
                            },
                            'click button.remove': function(e) {
                                e.preventDefault();
                                HoneySens.request('events:filters:remove', this.model);
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
                grid.sort('id', 'descending');
                // Division Filter
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
            }
        });
    });

    return HoneySens.Events.Views.FilterList;
});