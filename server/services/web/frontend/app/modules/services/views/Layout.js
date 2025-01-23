import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/services/templates/Layout.tpl';
import 'app/views/regions';

HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        }
    });
});

export default HoneySens.Services.Views.Layout;