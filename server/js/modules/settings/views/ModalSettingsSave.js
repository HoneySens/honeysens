define(['app/app',
        'tpl!app/modules/settings/templates/ModalSettingsSave.tpl'],
function(HoneySens, ModalSettingsSaveTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalSettingsSave = Marionette.ItemView.extend({
            template: ModalSettingsSaveTpl
        });
    });

    return HoneySens.Settings.Views.ModalSettingsSave;
});