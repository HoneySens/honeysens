define(['app/app',
        'tpl!app/modules/sensors/templates/ModalSensorRemove.tpl'],
function(HoneySens, ModalSensorRemoveTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalSensorRemove = Marionette.ItemView.extend({
            template: ModalSensorRemoveTpl,
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    var id = this.model.id;
                    this.model.destroy({wait: true, success: function() {
                        HoneySens.execute('fetchUpdates');
                        // Update events manually, since event deletes aren't covered by global updates (for performance reasons)
                        HoneySens.data.models.events.remove(HoneySens.data.models.events.filter(function(event) {return event.get('sensor') == id;}));
                        HoneySens.request('view:modal').empty();
                    }});
                }
            }
        });
    });

    return HoneySens.Sensors.Views.ModalSensorRemove;
});