import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import LayoutTpl from 'app/modules/accounts/templates/Layout.tpl';

const Layout = View.extend({
    template: _.template(LayoutTpl),
    templateContext: {...i18n},
    regions: {
        content: { el: 'div.content' }
    }
});

export default Layout;
