import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/events/templates/Layout.tpl';

HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.LayoutView.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        },
        initialize: function() {
            this.listenTo(HoneySens.vent, 'events:shown', function() {
                this.$el.find('span.title').html(_.t("events:eventHeader"));
            });
            this.listenTo(HoneySens.vent, 'events:filters:shown', function() {
                this.$el.find('span.title').html(`${_.t("events:eventHeader")} &rsaquo; ${_.t("events:filterHeader")}`);
            });
        }
    });
});

export default HoneySens.Events.Views.Layout;