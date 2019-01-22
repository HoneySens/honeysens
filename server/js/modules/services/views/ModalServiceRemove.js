define(['app/app',
        'tpl!app/modules/services/templates/ModalServiceRemove.tpl'],
function(HoneySens, ModalServiceRemoveTpl) {
    HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalServiceRemove = Marionette.ItemView.extend({
            template: ModalServiceRemoveTpl,
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

    return HoneySens.Services.Views.ModalServiceRemove;
});
