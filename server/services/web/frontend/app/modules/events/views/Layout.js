import { radio } from 'app/radio';
import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/events/templates/Layout.tpl';

HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.View.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        },
        initialize: function() {
            this.listenTo(radio, 'events:shown', function() {
                this.$el.find('span.title').html(_.t("events:eventHeader"));
            });
            this.listenTo(radio, 'events:filters:shown', function() {
                this.$el.find('span.title').html(`${_.t("events:eventHeader")} &rsaquo; ${_.t("events:filterHeader")}`);
            });
        }
    });
});

export default HoneySens.Events.Views.Layout;