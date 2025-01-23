import HoneySens from 'app/app';
import ModalSensorStatusItemTpl from 'app/modules/sensors/templates/ModalSensorStatusItem.tpl';

HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalSensorStatusItem = Marionette.ItemView.extend({
        template: _.template(ModalSensorStatusItemTpl),
        tagName: 'tr',
        templateHelpers: {
            showTimestamp: function() {
                var ts = this.timestamp;
                return ('0' + ts.getDate()).slice(-2) + '.' + ('0' + (ts.getMonth() + 1)).slice(-2) + '.' +
                    ts.getFullYear() + ' ' + ('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2);
            }
        }
    });
});

export default HoneySens.Sensors.Views.ModalSensorStatusItem;