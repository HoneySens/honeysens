import { View } from 'backbone.marionette';
import LayoutTpl from 'app/modules/setup/templates/Layout.tpl';
import _ from 'underscore';

const Layout = View.extend({
    template: _.template(LayoutTpl),
    regions: {
        content: {
            el: 'div.content'
        }
    }
});

export default Layout;
