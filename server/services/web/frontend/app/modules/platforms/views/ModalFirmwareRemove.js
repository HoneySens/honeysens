import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import ModalFirmwareRemoveTpl from 'app/modules/platforms/templates/ModalFirmwareRemove.tpl';

const ModalFirmwareRemove = View.extend({
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
        ...i18n,
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

export default ModalFirmwareRemove;
