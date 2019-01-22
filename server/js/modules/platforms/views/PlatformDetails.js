define(['app/app',
        'backgrid',
        'tpl!app/modules/platforms/templates/PlatformDetails.tpl',
        'tpl!app/modules/platforms/templates/FirmwareListActionsCell.tpl'],
function(HoneySens, Backgrid, PlatformDetailsTpl, FirmwareListActionsCellTpl) {
    HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.PlatformDetails = Marionette.LayoutView.extend({
            template: PlatformDetailsTpl,
            className: 'container-fluid',
            regions: {
                firmware: 'div.firmware'
            },
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                }
            },
            onRender: function() {
                var modelCollection = this.model.getFirmwareRevisions(),
                    view = this,
                    columns = [{
                        name: 'name',
                        label: 'Name',
                        editable: false,
                        cell: 'string'
                    }, {
                        name: 'version',
                        label: 'Version',
                        editable: false,
                        cell: 'string'
                    }, {
                        name: 'description',
                        label: 'Beschreibung',
                        editable: false,
                        cell: 'string'
                    }, {
                        name: 'Aktionen',
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            template: FirmwareListActionsCellTpl,
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
                                    HoneySens.request('platforms:firmware:remove', this.model);
                                }
                            },
                            render: function() {
                                // Hide action buttons for the default firmware revision
                                this.model.set('nondef', this.model.id !== view.model.get('default_firmware_revision'));
                                this.$el.html(this.template(this.model.attributes));
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
                this.firmware.show(grid);
                grid.sort('version', 'descending');
            }
        });
    });

    return HoneySens.Platforms.Views.PlatformDetails;
});