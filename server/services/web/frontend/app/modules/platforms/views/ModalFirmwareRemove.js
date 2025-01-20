define(['app/app',
        'app/modules/platforms/templates/ModalFirmwareRemove.tpl'],
function(HoneySens, ModalFirmwareRemoveTpl) {
    HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalFirmwareRemove = Marionette.ItemView.extend({
            template: _.template(ModalFirmwareRemoveTpl),
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    this.model.destroy({
                        wait: true,
                        success: function() {
                            HoneySens.request('view:modal').empty();
                            HoneySens.data.models.platforms.fetch();
                        },
                        error: function() {
                            HoneySens.request('view:modal').empty();
                        }
                    });
                }
            },
            templateHelpers: {
                hasAffectedSensors: function() {
                    let firmware = this.id,
                        affectedSensors = HoneySens.data.models.sensors.filter(function(s) {
                        return s.get('firmware') === firmware;
                    });
                    return affectedSensors.length > 0;
                },
                getAffectedSensors: function() {
                    let firmware = this.id;
                    return HoneySens.data.models.sensors.filter(function(s) {
                        return s.get('firmware') === firmware;
                    }).map(function(s) {
                        return s.get('name') + ' (' + s.id + ')';
                    }).join(', ');
                }
            }
        });
    });

    return HoneySens.Platforms.Views.ModalFirmwareRemove;
});