define(['app/app',
        'tpl!app/modules/sensors/templates/ModalSensorStatusItem.tpl'],
function(HoneySens, ModalSensorStatusItemTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalSensorStatusItem = Marionette.ItemView.extend({
            template: ModalSensorStatusItemTpl,
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

    return HoneySens.Sensors.Views.ModalSensorStatusItem;
});