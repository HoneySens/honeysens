import HoneySens from 'app/app';
import ModalFilterRemoveTpl from 'app/modules/events/templates/ModalFilterRemove.tpl';

HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalFilterRemove = Marionette.ItemView.extend({
        template: _.template(ModalFilterRemoveTpl),
        events: {
            'click button.btn-primary': function(e) {
                e.preventDefault();
                this.model.destroy({wait: true, success: function() {
                    HoneySens.request('view:modal').empty();
                }});
            }
        }
    });
});

export default HoneySens.Events.Views.ModalFilterRemove;