import HoneySens from 'app/app';
import ModalServiceRemoveTpl from 'app/modules/services/templates/ModalServiceRemove.tpl';

HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalServiceRemove = Marionette.ItemView.extend({
        template: _.template(ModalServiceRemoveTpl),
        events: {
            'click button.btn-primary': function(e) {
                e.preventDefault();
                this.model.destroy({
                    wait: true,
                    success: function() {
                        HoneySens.request('view:modal').empty();
                    },
                    error: function() {
                        HoneySens.request('view:modal').empty();
                    }
                });
            }
        }
    });
});

export default HoneySens.Services.Views.ModalServiceRemove;