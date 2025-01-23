import HoneySens from 'app/app';
import EndpointTpl from 'app/modules/setup/templates/Endpoint.tpl';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Endpoint = Marionette.ItemView.extend({
        template: _.template(EndpointTpl),
        events: {
            'click button:submit': function(e) {
                e.preventDefault();
                this.$el.find('form').trigger('submit');
            }
        },
        onRender: function() {
            var view = this;

            this.$el.find('form').validator().on('submit', function (e) {
                if (!e.isDefaultPrevented()) {
                    e.preventDefault();

                    var serverEndpoint = view.$el.find('input[name="serverEndpoint"]').val();
                    view.model.set({serverEndpoint: serverEndpoint});
                    HoneySens.request('setup:install:show', {step: 3, model: view.model});
                }
            });
        },
        templateHelpers: {
            showCertCN: function() {
                return HoneySens.data.system.get('cert_cn');
            }
        }
    });

});

export default HoneySens.Setup.Views.Endpoint;