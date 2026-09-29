import i18n from 'app/common/i18n';
import { CollectionView } from 'backbone.marionette';
import ModalSensorStatusItemView from 'app/modules/sensors/views/ModalSensorStatusItem';
import ModalSensorStatusListTpl from 'app/modules/sensors/templates/ModalSensorStatusList.tpl';

const ModalSensorStatusList = CollectionView.extend({
    template: _.template(ModalSensorStatusListTpl),
    templateContext: {...i18n},
    childViewContainer: 'tbody',
    childView: ModalSensorStatusItemView,
    attachHtml: function(collectionView, childView) {
        collectionView.$el.find(this.childViewContainer).prepend(childView.el);
    }
});

export default ModalSensorStatusList;
