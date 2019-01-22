define(['app/app',
        'tpl!app/modules/services/templates/ModalServiceRevisionRemove.tpl'],
function(HoneySens, ModalServiceRevisionRemoveTpl) {
    HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalServiceRevisionRemove = Marionette.ItemView.extend({
            template: ModalServiceRevisionRemoveTpl,
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

    return HoneySens.Services.Views.ModalServiceRevisionRemove;
});