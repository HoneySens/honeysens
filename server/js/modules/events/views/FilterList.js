define(['app/app',
        'app/models',
        'backgrid',
        'tpl!app/modules/events/templates/FilterList.tpl',
        'tpl!app/modules/events/templates/FilterListStatusCell.tpl',
        'tpl!app/modules/events/templates/FilterListActionsCell.tpl',
        'app/views/common',
        'backgrid-select-filter'],
function(HoneySens, Models, Backgrid, FilterListTpl, FilterListStatusCellTpl, FilterListActionsCellTpl) {
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
                    name: 'division',
                    label: 'Gruppe',
                    editable: false,
                    sortType: 'toggle',
                    cell: Backgrid.Cell.extend({
                        render: function() {
                            if(this.model.has('division')) {
                                var division_id = this.model.get('division');
                                this.$el.html(HoneySens.data.models.divisions.get(division_id).get('name'));
                            }
                            return this;
                        }
                    })
                }, {
                    name: 'name',
                    label: 'Name',
                    editable: false,
                    cell: 'string'
                }, {
                    name: 'count',
                    label: 'Zähler',
                    editable: false,
                    cell: Backgrid.IntegerCell.extend({
                        orderSeparator: ''
                    })
                }, {
                    name: 'enabled',
                    label: 'Status',
                    editable: false,
                    cell: Backgrid.Cell.extend({
                        template: FilterListStatusCellTpl,
                        render: function() {
                            this.$el.html(this.template(this.model.attributes));
                            if(this.model.get('enabled')) this.$el.removeClass('danger').addClass('success');
                            else this.$el.removeClass('success').addClass('danger');
                            return this;
                        }
                    })
                }, {
                    label: 'Aktionen',
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: FilterListActionsCellTpl,
                        events: {
                            'click button.toggle': function(e) {
                                e.preventDefault();
                                HoneySens.request('events:filters:toggle', this.model);
                            },
                            'click button.edit': function(e) {
                                e.preventDefault();
                                HoneySens.request('events:filters:edit', this.model);
                            },
                            'click button.remove': function(e) {
                                e.preventDefault();
                                HoneySens.request('events:filters:remove', this.model);
                            }
                        },
                        initialize: function(options) {
                           // Re-render this cell on model changes
                            this.listenTo(this.model, 'change', function() {
                                this.render();
                            });
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
            },
            templateHelpers: {
                hasDivision: function() {
                    // checks whether there is at least one division available
                    return HoneySens.data.models.divisions.length > 0;
                }
            }
        });
    });

    return HoneySens.Events.Views.FilterList;
});