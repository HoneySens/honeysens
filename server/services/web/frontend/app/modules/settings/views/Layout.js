import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/settings/templates/Layout.tpl';
import 'app/views/common';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {content: 'div.content'}
    });
});

export default HoneySens.Settings.Views.Layout;