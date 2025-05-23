import HoneySens from 'app/app';
import ModalServerErrorTpl from 'app/common/templates/ModalServerError.tpl';

HoneySens.module('Common.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalServerError = Marionette.ItemView.extend({
        template: _.template(ModalServerErrorTpl),
        templateHelpers: {
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
                return msg !== null ? msg : _.t('genericServerError');
            }
        },
        onDestroy: function() {
            if(this.model.has('onClose')) this.model.attributes.onClose();
        }
    });
});

export default HoneySens.Common.Views.ModalServerError;