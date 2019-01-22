define(['app/app', 'tpl!app/modules/accounts/templates/ModalRemoveDivision.tpl'],
function(HoneySens, ModalRemoveDivisionTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalRemoveDivision = Marionette.ItemView.extend({
            template: ModalRemoveDivisionTpl,
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    this.model.destroy({ wait: true, success: function() {
                        HoneySens.request('view:modal').empty();
                    }});
                }
            }
        });
    });

    return HoneySens.Accounts.Views.ModalRemoveDivision;
});