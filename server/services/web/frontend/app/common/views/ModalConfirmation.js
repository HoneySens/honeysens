import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import ModalConfirmationTpl from 'app/common/templates/ModalConfirmation.tpl';

/**
 * Expects to be passed a model with params
 * - msg: The message string to show
 * - onConfirm: Optional callback function in case of confirmation
 * - onClose: Optional callback function called when the modal closes (regardless of user selection)
 */
const ModalConfirmation = View.extend({
    template: _.template(ModalConfirmationTpl),
    templateContext: {...i18n},
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

export default ModalConfirmation;
