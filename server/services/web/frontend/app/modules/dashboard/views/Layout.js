import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import Regions from 'app/views/regions';
import LayoutTpl from 'app/modules/dashboard/templates/Layout.tpl';

const Layout = View.extend({
    template: _.template(LayoutTpl),
    templateContext: {...i18n},
    regions: {
        content: {
            el: 'div.content',
            regionClass: Regions.TransitionRegion
    }},
    initialize: function() {
        this.getRegion('content').concurrentTransition = true;
    }
});

export default Layout;
