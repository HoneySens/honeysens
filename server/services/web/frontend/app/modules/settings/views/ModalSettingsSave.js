import HoneySens from 'app/app';
import ModalSettingsSaveTpl from 'app/modules/settings/templates/ModalSettingsSave.tpl';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalSettingsSave = Marionette.ItemView.extend({
        template: _.template(ModalSettingsSaveTpl)
    });
});

export default HoneySens.Settings.Views.ModalSettingsSave;