import { radio } from 'app/radio';
import HoneySens from 'app/app';
import ModalSensorRemoveTpl from 'app/modules/sensors/templates/ModalSensorRemove.tpl';

HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalSensorRemove = Marionette.View.extend({
        template: _.template(ModalSensorRemoveTpl),
        events: {
            'click button.btn-primary': function(e) {
                e.preventDefault();
                let archive = this.$el.find('input[name="archive"]').is(':checked'),
                    id = this.model.id;
                $.ajax({
                    type: 'DELETE',
                    url: 'api/sensors/' + id,
                    data: JSON.stringify({archive: archive}),
                    success: function() {
                        radio.request('fetchUpdates', false);
                        // Update events manually, since event deletes aren't covered by global updates (for performance reasons)
                        HoneySens.data.models.events.remove(HoneySens.data.models.events.filter(function(event) {return event.get('sensor') == id;}));
                        radio.request('view:modal').empty();
                    }
                });
            }
        },
        templateContext: {
            archivePrefer: function() {
                return HoneySens.data.settings.get('archivePrefer');
            }
        }
    });
});

export default HoneySens.Sensors.Views.ModalSensorRemove;