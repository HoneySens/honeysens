import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import ModalForwardTestEventTpl from 'app/modules/settings/templates/ModalForwardTestEvent.tpl';
import 'validator';

const ModalForwardTestEvent = View.extend({
    template:  _.template(ModalForwardTestEventTpl),
    templateContext: {
        ...i18n,
        showTimestamp: function() {
            var ts = new Date(this.timestamp * 1000);
            return ts.toISOString();
        }
    }
});

export default ModalForwardTestEvent;
