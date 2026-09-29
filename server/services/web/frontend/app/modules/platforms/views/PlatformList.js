import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import Backgrid from 'backgrid';
import PlatformListTpl from 'app/modules/platforms/templates/PlatformList.tpl';
import PlatformListActionsCellTpl from 'app/modules/platforms/templates/PlatformListActionsCell.tpl';

const PlatformList = View.extend({
    template: _.template(PlatformListTpl),
    templateContext: {...i18n},
    className: 'row',
    regions: {
        list: 'div.table-responsive'
    },
    events: {
        'click button.add': function(e) {
            e.preventDefault();
            radio.request('platforms:firmware:add');
        }
    },
    onRender: function() {
        var columns = [{
            name: 'title',
            label: i18n.t('name'),
            editable: false,
            cell: 'string'
        }, {
            name: 'description',
            label: i18n.t('platforms:firmwareDescription'),
            editable: false,
            sortable: false,
            cell: 'string'
        }, {
            label: i18n.t('actions'),
            editable: false,
            sortable: false,
            cell: Backgrid.Cell.extend({
                template: _.template(PlatformListActionsCellTpl),
                events: {
                    'click button.showDetails': function(e) {
                        e.preventDefault();
                        radio.request('platforms:details', this.model);
                    }
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
        grid.sort('title', 'ascending');
    }
});

export default PlatformList;
