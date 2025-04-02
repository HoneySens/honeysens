import HoneySens from 'app/app';
import ModalSensorStatusItemView from 'app/modules/sensors/views/ModalSensorStatusItem';
import ModalSensorStatusListTpl from 'app/modules/sensors/templates/ModalSensorStatusList.tpl';

HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalSensorStatusList = Marionette.CompositeView.extend({
        template: _.template(ModalSensorStatusListTpl),
        childViewContainer: 'tbody',
        childView: ModalSensorStatusItemView,
        attachHtml: function(collectionView, childView) {
            collectionView.$el.find(this.childViewContainer).prepend(childView.el);
        }
    });
});

export default HoneySens.Sensors.Views.ModalSensorStatusList;