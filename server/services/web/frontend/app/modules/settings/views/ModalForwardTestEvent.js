import HoneySens from 'app/app';
import ModalForwardTestEventTpl from 'app/modules/settings/templates/ModalForwardTestEvent.tpl';
import 'validator';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalForwardTestEvent = Marionette.ItemView.extend({
        template:  _.template(ModalForwardTestEventTpl),
        templateHelpers: {
            showTimestamp: function() {
                var ts = new Date(this.timestamp * 1000);
                return ts.toISOString();
            }
        }
    })
});

export default HoneySens.Settings.Views.ModalForwardTestEvent;