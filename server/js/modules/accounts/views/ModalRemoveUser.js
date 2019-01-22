define(['app/app', 'tpl!app/modules/accounts/templates/ModalRemoveUser.tpl'],
function(HoneySens, ModalRemoveUserTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalRemoveUser = Marionette.ItemView.extend({
            template: ModalRemoveUserTpl,
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

    return HoneySens.Accounts.Views.ModalRemoveUser;
});