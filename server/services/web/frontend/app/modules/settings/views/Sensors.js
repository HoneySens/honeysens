import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import SensorsTpl from 'app/modules/settings/templates/Sensors.tpl';
import 'validator';

const Sensors = View.extend({
    template: _.template(SensorsTpl),
    templateContext: {...i18n},
    className: 'panel-body',
    onRender: function() {
        var view = this;
        this.$el.find('[data-toggle="popover"]').popover();
        this.$el.find('form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();

                var updateInterval = parseInt(view.$el.find('input[name="updateInterval"]').val()),
                    serviceNetwork = view.$el.find('input[name="serviceNetwork"]').val(),
                    timeoutThreshold = parseInt(view.$el.find('input[name="timeoutThreshold"]').val());
                view.model.save({
                    sensorsUpdateInterval: updateInterval,
                    sensorsServiceNetwork: serviceNetwork,
                    sensorsTimeoutThreshold: timeoutThreshold}, {
                    success: function() {
                        radio.request('view:modal').show(new ModalSettingsSaveView());
                    }
                });
            }
        });
    }
});

export default Sensors;
