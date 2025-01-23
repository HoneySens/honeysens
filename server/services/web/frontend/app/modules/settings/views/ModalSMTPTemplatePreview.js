import HoneySens from 'app/app';
import ModalSMTPTemplatePreviewTpl from 'app/modules/settings/templates/ModalSMTPTemplatePreview.tpl';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalSMTPTemplatePreview = Marionette.ItemView.extend({
        template: _.template(ModalSMTPTemplatePreviewTpl),
    });
});

export default HoneySens.Settings.Views.ModalSMTPTemplatePreview;