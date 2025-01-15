define(['app/app',
        'app/modules/sensors/views/ModalSensorStatusItem',
        'app/modules/sensors/templates/ModalSensorStatusList.tpl'],
function(HoneySens, ModalSensorStatusItemView, ModalSensorStatusListTpl) {
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

    return HoneySens.Sensors.Views.ModalSensorStatusList;
});