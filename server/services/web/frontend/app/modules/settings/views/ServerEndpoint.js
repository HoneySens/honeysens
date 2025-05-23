import HoneySens from 'app/app';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import ServerEndpointTpl from 'app/modules/settings/templates/ServerEndpoint.tpl';
import 'validator';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ServerEndpoint = Marionette.ItemView.extend({
        template: _.template(ServerEndpointTpl),
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
                            HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                        }
                    });
                }
            });
        }
    });
});

export default HoneySens.Settings.Views.ServerEndpoint;