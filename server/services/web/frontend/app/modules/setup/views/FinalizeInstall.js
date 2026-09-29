import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import FinalizeInstallTpl from 'app/modules/setup/templates/FinalizeInstall.tpl';

const FinalizeInstall = View.extend({
    template: _.template(FinalizeInstallTpl),
    templateContext: {...i18n},
    events: {
        'click button': function(e) {
            e.preventDefault();
            radio.trigger('logout:success');
        }
    }
});

export default FinalizeInstall;
