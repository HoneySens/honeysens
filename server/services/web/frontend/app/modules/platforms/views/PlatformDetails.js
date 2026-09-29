import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import Backgrid from 'backgrid';
import PlatformDetailsTpl from 'app/modules/platforms/templates/PlatformDetails.tpl';
import FirmwareListActionsCellTpl from 'app/modules/platforms/templates/FirmwareListActionsCell.tpl';

const PlatformDetails = View.extend({
    template: _.template(PlatformDetailsTpl),
    templateContext: {...i18n},
    className: 'container-fluid',
    regions: {
        firmware: 'div.firmware'
    },
    events: {
        'click button.cancel': function() {
            radio.request('view:content').getRegion('overlay').empty();
        }
    },
    onRender: function() {
        var modelCollection = this.model.getFirmwareRevisions(),
            view = this,
            columns = [{
                name: 'name',
                label: i18n.t('name'),
                editable: false,
                cell: 'string'
            }, {
                name: 'version',
                label: i18n.t('platforms:firmwareVersion'),
                editable: false,
                cell: 'string'
            }, {
                name: 'description',
                label: i18n.t('platforms:firmwareDescription'),
                editable: false,
                cell: 'string'
            }, {
                name: i18n.t('actions'),
                editable: false,
                sortable: false,
                cell: Backgrid.Cell.extend({
                    template: _.template(FirmwareListActionsCellTpl),
                    events: {
                        'click button.setDefaultFirmware': function(e) {
                            e.preventDefault();
                            view.model.save({default_firmware_revision: this.model.id}, {
                                wait: true,
                                success: function() {
                                    // Force grid redraw
                                    modelCollection.trigger('reset');
                                }
                            });
                        },
                        'click button.removeFirmware': function(e) {
                            e.preventDefault();
                            radio.request('platforms:firmware:remove', this.model);
                        }
                    },
                    render: function() {
                        // Hide action buttons for the default firmware revision
                        this.model.set('nondef', this.model.id !== view.model.get('default_firmware_revision'));
                        this.$el.html(this.template(_.extend({t: i18n.t}, this.model.attributes)));
                        this.$el.find('button, a').tooltip();
                        return this;
                    }
                })
            }];
        var row = Backgrid.Row.extend({
            render: function() {
                Backgrid.Row.prototype.render.call(this);
                if(this.model.id === view.model.get('default_firmware_revision')) this.$el.addClass('warning');
                return this;
            }
        });
        var grid = new Backgrid.Grid({
            row: row,
            columns: columns,
            collection: modelCollection,
            className: 'table table-striped'
        });
        this.getRegion('firmware').show(grid);
        grid.sort('version', 'descending');
    }
});

export default PlatformDetails;
