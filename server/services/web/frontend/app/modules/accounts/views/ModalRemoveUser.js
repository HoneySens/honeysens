import HoneySens from 'app/app';
import ModalRemoveUserTpl from 'app/modules/accounts/templates/ModalRemoveUser.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalRemoveUser = Marionette.ItemView.extend({
        template: _.template(ModalRemoveUserTpl),
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

export default HoneySens.Accounts.Views.ModalRemoveUser;