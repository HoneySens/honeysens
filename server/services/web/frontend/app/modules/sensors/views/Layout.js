import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/sensors/templates/Layout.tpl';
import 'app/views/common';

HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        }
    });
});

export default HoneySens.Sensors.Views.Layout;