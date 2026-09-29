import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import ModalServerErrorTpl from 'app/common/templates/ModalServerError.tpl';

const ModalServerError = View.extend({
    template: _.template(ModalServerErrorTpl),
    templateContext: {
        ...i18n,
        getMessage: function() {
            var msg = null;
            try {
                // In case a 'msg' property is defined, use it.
                if(this.hasOwnProperty('msg')) msg = this.msg;
                else {
                    // Otherwise, try to parse the response as JSON and lookup the 'code' attribute
                    var code = JSON.parse(this.xhr.responseText).code;
                    if (this.errors.hasOwnProperty(code)) msg = this.errors[code];
                }
            } catch(e) {}
            return msg !== null ? msg : i18n.t('genericServerError');
        }
    },
    onDestroy: function() {
        if(this.model.has('onClose')) this.model.attributes.onClose();
    }
});

export default ModalServerError;
