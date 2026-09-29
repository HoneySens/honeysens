import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import EndpointTpl from 'app/modules/setup/templates/Endpoint.tpl';

const Endpoint = View.extend({
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
                radio.request('setup:install:show', {step: 3, model: view.model});
            }
        });
    },
    templateContext: {
        ...i18n,
        showCertCN: function() {
            return HoneySens.data.system.get('cert_cn');
        }
    }
});

export default Endpoint;
