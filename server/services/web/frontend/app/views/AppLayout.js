import HoneySens from 'app/app';
import Regions from 'app/views/regions';
import AppLayoutTpl from 'app/templates/AppLayout.tpl';

HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.AppLayout = Marionette.View.extend({
        template: _.template(AppLayoutTpl),
        className: 'horizontalContent',
        regions: {
            sidebar: '#sidebar',
            main: '#main',
            overlay: {el: '#overlay', regionClass: Regions.OverlayRegion}
        }
    });
});

export default HoneySens.Views.AppLayout;