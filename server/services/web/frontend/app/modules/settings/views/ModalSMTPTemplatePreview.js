import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import ModalSMTPTemplatePreviewTpl from 'app/modules/settings/templates/ModalSMTPTemplatePreview.tpl';
import _ from 'underscore';

const ModalSMTPTemplatePreview = View.extend({
    template: _.template(ModalSMTPTemplatePreviewTpl),
    templateContext: {...i18n},
});

export default ModalSMTPTemplatePreview;
