import HoneySens from 'app/app';
import Backgrid from 'backgrid';
import FilterListTpl from 'app/modules/events/templates/FilterList.tpl';
import FilterListStatusCellTpl from 'app/modules/events/templates/FilterListStatusCell.tpl';
import FilterListActionsCellTpl from 'app/modules/events/templates/FilterListActionsCell.tpl';
import 'app/views/common';
import 'backgrid-select-filter';

HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.FilterList = Marionette.LayoutView.extend({
        template: _.template(FilterListTpl),
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
                label: _.t('id'),
                editable: false,
                cell: Backgrid.IntegerCell.extend({
                    orderSeparator: ''
                })
            }, {
                name: 'division',
                label: _.t('division'),
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
                label: _.t('name'),
                editable: false,
                cell: 'string'
            }, {
                name: 'count',
                label: _.t('events:filterListCounter'),
                editable: false,
                cell: Backgrid.IntegerCell.extend({
                    orderSeparator: ''
                })
            }, {
                name: 'enabled',
                label: _.t('events:filterListStatus'),
                editable: false,
                cell: Backgrid.Cell.extend({
                    template: _.template(FilterListStatusCellTpl),
                    render: function() {
                        this.$el.html(this.template(this.model.attributes));
                        if(this.model.get('enabled')) this.$el.removeClass('danger').addClass('success');
                        else this.$el.removeClass('success').addClass('danger');
                        return this;
                    }
                })
            }, {
                label: _.t('actions'),
                editable: false,
                sortable: false,
                cell: Backgrid.Cell.extend({
                    template: _.template(FilterListActionsCellTpl),
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
            var divisions = _.union([{label: _.t('allDivisions'), value: null}],
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

export default HoneySens.Events.Views.FilterList;