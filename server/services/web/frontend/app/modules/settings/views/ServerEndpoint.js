import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import ServerEndpointTpl from 'app/modules/settings/templates/ServerEndpoint.tpl';
import 'validator';

const ServerEndpoint = View.extend({
    template: _.template(ServerEndpointTpl),
    templateContext: {...i18n},
    className: 'panel-body',
    onRender: function() {
        var view = this;

        this.$el.find('form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();

                var serverHost = view.$el.find('input[name="serverHost"]').val();
                var serverPortHTTPS = parseInt(view.$el.find('input[name="serverPortHTTPS"]').val());
                view.model.save({serverHost: serverHost, serverPortHTTPS: serverPortHTTPS}, {
                    success: function() {
                        radio.request('view:modal').show(new ModalSettingsSaveView());
                    }
                });
            }
        });
    }
});

export default ServerEndpoint;
