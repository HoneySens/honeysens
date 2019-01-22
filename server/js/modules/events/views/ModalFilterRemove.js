define(['app/app',
        'tpl!app/modules/events/templates/ModalFilterRemove.tpl'],
function(HoneySens, ModalFilterRemoveTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalFilterRemove = Marionette.ItemView.extend({
            template: ModalFilterRemoveTpl,
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

    return HoneySens.Events.Views.ModalFilterRemove;
});