define(['app/app',
        'tpl!app/modules/settings/templates/ModalForwardTestEvent.tpl',
        'validator'],
function(HoneySens, ModalForwardTestEvent) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalForwardTestEvent = Marionette.ItemView.extend({
            template:ModalForwardTestEvent,
            templateHelpers: {
                showTimestamp: function() {
                    var ts = new Date(this.timestamp * 1000);
                    return ts.toISOString();
                }
            }
        })
    });

    return HoneySens.Settings.Views.ModalForwardTestEvent;
});