import HoneySens from 'app/app';
import Regions from 'app/views/regions';
import LayoutTpl from 'app/modules/dashboard/templates/Layout.tpl';
import 'app/views/common';

HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: {
                selector: 'div.content',
                regionClass: Regions.TransitionRegion
        }},
        initialize: function() {
            this.getRegion('content').concurrentTransition = true;
        }
    });
});

export default HoneySens.Dashboard.Views.Layout;