import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/platforms/templates/Layout.tpl';
import 'app/views/regions';

HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        }
    });
});

export default HoneySens.Platforms.Views.Layout;