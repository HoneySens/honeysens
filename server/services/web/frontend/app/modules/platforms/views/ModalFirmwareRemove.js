import { radio } from 'app/radio';
import HoneySens from 'app/app';
import ModalFirmwareRemoveTpl from 'app/modules/platforms/templates/ModalFirmwareRemove.tpl';

HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalFirmwareRemove = Marionette.View.extend({
        template: _.template(ModalFirmwareRemoveTpl),
        events: {
            'click button.btn-primary': function(e) {
                e.preventDefault();
                this.model.destroy({
                    wait: true,
                    success: function() {
                        radio.request('view:modal').empty();
                        HoneySens.data.models.platforms.fetch();
                    },
                    error: function() {
                        radio.request('view:modal').empty();
                    }
                });
            }
        },
        templateContext: {
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

export default HoneySens.Platforms.Views.ModalFirmwareRemove;