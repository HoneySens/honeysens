import HoneySens from 'app/app';
import Regions from 'app/views/regions';
import AppLayoutTpl from 'app/templates/AppLayout.tpl';

HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.AppLayout = Marionette.LayoutView.extend({
        template: _.template(AppLayoutTpl),
        className: 'horizontalContent',
        regions: {
            sidebar: '#sidebar',
            main: '#main',
            overlay: {selector: '#overlay', regionClass: Regions.OverlayRegion}
        }
    });
});

export default HoneySens.Views.AppLayout;