define(['app/app',
        'tpl!app/modules/platforms/templates/ModalFirmwareRemove.tpl'],
function(HoneySens, ModalFirmwareRemoveTpl) {
    HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalFirmwareRemove = Marionette.ItemView.extend({
            template: ModalFirmwareRemoveTpl,
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    this.model.destroy({
                        wait: true,
                        success: function() {
                            HoneySens.request('view:modal').empty();
                            HoneySens.data.models.platforms.fetch();
                        },
                        error: function() {
                            HoneySens.request('view:modal').empty();
                        }
                    });
                }
            }
        });
    });

    return HoneySens.Platforms.Views.ModalFirmwareRemove;
});