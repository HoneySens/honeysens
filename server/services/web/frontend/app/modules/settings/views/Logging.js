import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import LoggingTpl from 'app/modules/settings/templates/Logging.tpl';
import 'validator';

const Logging = View.extend({
    template: _.template(LoggingTpl),
    templateContext: {...i18n},
    className: 'panel-body',
    onRender: function() {
        var view = this;
        this.$el.find('[data-toggle="popover"]').popover();
        this.$el.find('form').validator().on('submit', function(e) {
            if(!e.isDefaultPrevented()) {
                e.preventDefault();

                var keepDays = parseInt(view.$el.find('input[name="keepDays"]').val());
                view.model.save({
                    apiLogKeepDays: keepDays}, {
                    success: function() {
                        radio.request('view:modal').show(new ModalSettingsSaveView());
                    }
                });
            }
        });
    }
});

export default Logging;
