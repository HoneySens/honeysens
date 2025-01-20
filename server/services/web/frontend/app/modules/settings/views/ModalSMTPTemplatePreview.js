define(['app/app',
        'app/modules/settings/templates/ModalSMTPTemplatePreview.tpl'],
function(HoneySens, ModalSMTPTemplatePreviewTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalSMTPTemplatePreview = Marionette.ItemView.extend({
            template: _.template(ModalSMTPTemplatePreviewTpl),
        });
    });

    return HoneySens.Settings.Views.ModalSMTPTemplatePreview;
});
