import HoneySens from 'app/app';
import FinalizeInstallTpl from 'app/modules/setup/templates/FinalizeInstall.tpl';

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

export default HoneySens.Setup.Views.FinalizeInstall;