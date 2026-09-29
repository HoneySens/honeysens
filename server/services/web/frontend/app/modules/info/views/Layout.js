import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import LayoutTpl from 'app/modules/info/templates/Layout.tpl';
import 'app/views/regions';

const Layout = View.extend({
    template: _.template(LayoutTpl),
    regions: {
        content: 'div.content'
    },
    initialize: function() {
        this.listenTo(radio, 'info:shown', function() {
            this.$el.find('span.title').html('HoneySens');
        });
    }
});

export default Layout;
