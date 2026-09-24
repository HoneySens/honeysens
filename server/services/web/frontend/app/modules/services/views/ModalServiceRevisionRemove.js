import { radio } from 'app/radio';
import HoneySens from 'app/app';
import ModalServiceRevisionRemoveTpl from 'app/modules/services/templates/ModalServiceRevisionRemove.tpl';

HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalServiceRevisionRemove = Marionette.View.extend({
        template: _.template(ModalServiceRevisionRemoveTpl),
        events: {
            'click button.btn-primary': function(e) {
                e.preventDefault();
                this.model.destroy({
                    wait: true,
                    success: function() {
                        radio.request('view:modal').empty();
                    },
                    error: function() {
                        radio.request('view:modal').empty();
                    }
                });
            }
        }
    });
});

export default HoneySens.Services.Views.ModalServiceRevisionRemove;