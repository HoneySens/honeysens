define(['app/app',
        'tpl!app/modules/setup/templates/Landing.tpl'],
function(HoneySens, LandingTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Landing = Marionette.ItemView.extend({
            template: LandingTpl,
            events: {
                'click button.install': function() {
                    HoneySens.request('setup:install:show', {step: 1, model: new Backbone.Model()});
                },
                'click button.update': function() {
                    HoneySens.request('setup:update:show', {step: 1, model: new Backbone.Model()});
                }
            }
        });
    });

    return HoneySens.Setup.Views.Landing;
});