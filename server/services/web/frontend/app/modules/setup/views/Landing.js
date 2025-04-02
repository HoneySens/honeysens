import HoneySens from 'app/app';
import LandingTpl from 'app/modules/setup/templates/Landing.tpl';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Landing = Marionette.ItemView.extend({
        template: _.template(LandingTpl),
        events: {
            'click button.install': function() {
                HoneySens.request('setup:install:show', {step: 1, model: new Backbone.Model()});
            }
        }
    });
});

export default HoneySens.Setup.Views.Landing;