import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import Backgrid from 'backgrid';
import FilterListTpl from 'app/modules/events/templates/FilterList.tpl';
import FilterListStatusCellTpl from 'app/modules/events/templates/FilterListStatusCell.tpl';
import FilterListActionsCellTpl from 'app/modules/events/templates/FilterListActionsCell.tpl';
import 'backgrid-select-filter';

const FilterList = View.extend({
    template: _.template(FilterListTpl),
    className: 'row',
    regions: {
        groupFilter: 'div.groupFilter',
        list: 'div.table-responsive'
    },
    events: {
        'click button.add': function(e) {
            e.preventDefault();
            radio.request('events:filters:add');
        }
    },
    onRender: function() {
        var columns = [{
            name: 'id',
            label: i18n.t('id'),
            editable: false,
            cell: Backgrid.IntegerCell.extend({
                orderSeparator: ''
            })
        }, {
            name: 'division',
            label: i18n.t('division'),
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
            label: i18n.t('name'),
            editable: false,
            cell: 'string'
        }, {
            name: 'count',
            label: i18n.t('events:filterListCounter'),
            editable: false,
            cell: Backgrid.IntegerCell.extend({
                orderSeparator: ''
            })
        }, {
            name: 'enabled',
            label: i18n.t('events:filterListStatus'),
            editable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(FilterListStatusCellTpl),
                render: function() {
                    this.$el.html(this.template(_.extend({t: i18n.t}, this.model.attributes)));
                    if(this.model.get('enabled')) this.$el.removeClass('danger').addClass('success');
                    else this.$el.removeClass('success').addClass('danger');
                    return this;
                }
            })
        }, {
            label: i18n.t('actions'),
            editable: false,
            sortable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(FilterListActionsCellTpl),
                events: {
                    'click button.toggle': function(e) {
                        e.preventDefault();
                        radio.request('events:filters:toggle', this.model);
                    },
                    'click button.edit': function(e) {
                        e.preventDefault();
                        radio.request('events:filters:edit', this.model);
                    },
                    'click button.remove': function(e) {
                        e.preventDefault();
                        radio.request('events:filters:remove', this.model);
                    }
                },
                initialize: function(options) {
                   // Re-render this cell on model changes
                    this.listenTo(this.model, 'change', function() {
                        this.render();
                    });
                },
                render: function() {
                    this.$el.html(this.template(_.extend({t: i18n.t}, this.model.attributes)));
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
        this.getRegion('list').show(grid);
        grid.sort('id', 'descending');
        // Division Filter
        var divisions = _.union([{label: i18n.t('allDivisions'), value: null}],
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
        this.getRegion('groupFilter').show(this.groupFilterView);
    },
    templateContext: {
        ...i18n,
        hasDivision: function() {
            // checks whether there is at least one division available
            return HoneySens.data.models.divisions.length > 0;
        }
    }
});

export default FilterList;
