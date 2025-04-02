import HoneySens from 'app/app';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import LoggingTpl from 'app/modules/settings/templates/Logging.tpl';
import 'validator';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Logging = Marionette.ItemView.extend({
        template: _.template(LoggingTpl),
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
                            HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                        }
                    });
                }
            });
        }
    });
});

export default HoneySens.Settings.Views.Logging;