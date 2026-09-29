import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import ErrorTpl from 'app/modules/setup/templates/Error.tpl';

const Error = View.extend({
    template: _.template(ErrorTpl),
    templateContext: {...i18n},
    onRender: function() {
        var errorText;
        switch(this.model.get('code')) {
            case 1: errorText = i18n.t('setup:errorConfigWrite'); break;
            default: errorText = i18n.t('genericServerError'); break;
        }
        this.$el.find('p').text(errorText);
    }
});

export default Error;
