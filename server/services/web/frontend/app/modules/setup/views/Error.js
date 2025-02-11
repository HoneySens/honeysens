import HoneySens from 'app/app';
import ErrorTpl from 'app/modules/setup/templates/Error.tpl';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Error = Marionette.ItemView.extend({
        template: _.template(ErrorTpl),
        onRender: function() {
            var errorText;
            switch(this.model.get('code')) {
                case 1: errorText = _.t('setup:errorConfigWrite'); break;
                default: errorText = _.t('genericServerError'); break;
            }
            this.$el.find('p').text(errorText);
        }
    });
});

export default HoneySens.Setup.Views.Error;