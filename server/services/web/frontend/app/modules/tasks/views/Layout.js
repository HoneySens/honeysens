import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/tasks/templates/Layout.tpl';
import 'app/views/common';
import 'app/views/regions';

HoneySens.module('Tasks.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        }
    })
});

export default HoneySens.Tasks.Views.Layout;