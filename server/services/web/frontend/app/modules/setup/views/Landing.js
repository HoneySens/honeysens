import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import Backbone from 'backbone';
import { radio } from 'app/radio';
import LandingTpl from 'app/modules/setup/templates/Landing.tpl';

const Landing = View.extend({
    template: _.template(LandingTpl),
    templateContext: {...i18n},
    events: {
        'click button.install': function() {
            radio.request('setup:install:show', {step: 1, model: new Backbone.Model()});
        }
    }
});

export default Landing;
