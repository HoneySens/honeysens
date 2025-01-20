define(['app/app',
        'app/modules/setup/templates/FinalizeInstall.tpl'],
function(HoneySens, FinalizeInstallTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.FinalizeInstall = Marionette.ItemView.extend({
            template: _.template(FinalizeInstallTpl),
            events: {
                'click button': function(e) {
                    e.preventDefault();
                    HoneySens.vent.trigger('logout:success');
                }
            }
        });
    });

    return HoneySens.Setup.Views.FinalizeInstall;
});