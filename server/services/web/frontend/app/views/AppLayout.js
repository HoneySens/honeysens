import { View } from 'backbone.marionette';
import Regions from 'app/views/regions';
import AppLayoutTpl from 'app/templates/AppLayout.tpl';

const AppLayout = View.extend({
    template: _.template(AppLayoutTpl),
    className: 'horizontalContent',
    regions: {
        sidebar: '#sidebar',
        main: '#main',
        overlay: {el: '#overlay', regionClass: Regions.OverlayRegion}
    }
});

export default AppLayout;
