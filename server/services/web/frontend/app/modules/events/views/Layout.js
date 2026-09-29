import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import LayoutTpl from 'app/modules/events/templates/Layout.tpl';

const Layout = View.extend({
    template: _.template(LayoutTpl),
    templateContext: {...i18n},
    regions: {
        content: 'div.content'
    },
    initialize: function() {
        this.listenTo(radio, 'events:shown', function() {
            this.$el.find('span.title').html(i18n.t("events:eventHeader"));
        });
        this.listenTo(radio, 'events:filters:shown', function() {
            this.$el.find('span.title').html(`${i18n.t("events:eventHeader")} &rsaquo; ${i18n.t("events:filterHeader")}`);
        });
    }
});

export default Layout;
