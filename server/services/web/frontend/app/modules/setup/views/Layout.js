import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/setup/templates/Layout.tpl';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: {
                selector: 'div.content'
            }
        }
    });
});

export default HoneySens.Setup.Views.Layout;