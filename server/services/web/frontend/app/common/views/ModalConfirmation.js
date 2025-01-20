define(['app/app',
        'app/common/templates/ModalConfirmation.tpl'],
function(HoneySens, ModalConfirmationTpl) {
    HoneySens.module('Common.Views', function (Views, HoneySens, Backbone, Marionette, $, _) {
        /**
         * Expects to be passed a model with params
         * - msg: The message string to show
         * - onConfirm: Optional callback function in case of confirmation
         * - onClose: Optional callback function called when the modal closes (regardless of user selection)
         */
        Views.ModalConfirmation = Marionette.ItemView.extend({
            template: _.template(ModalConfirmationTpl),
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    if(this.model.has('onConfirm')) this.model.attributes.onConfirm();
                }
            },
            onDestroy: function() {
                if(this.model.has('onClose')) this.model.attributes.onClose();
            }
        });
    });

    return HoneySens.Common.Views.ModalConfirmation;
});
