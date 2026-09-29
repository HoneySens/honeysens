import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import ModalSettingsSaveTpl from 'app/modules/settings/templates/ModalSettingsSave.tpl';

const ModalSettingsSave = View.extend({
    template: _.template(ModalSettingsSaveTpl),
    templateContext: {...i18n}
});

export default ModalSettingsSave;
