import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import LayoutTpl from 'app/modules/services/templates/Layout.tpl';
import 'app/views/regions';

const Layout = View.extend({
    template: _.template(LayoutTpl),
    templateContext: {...i18n},
    regions: {
        content: 'div.content'
    }
});

export default Layout;
