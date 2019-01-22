define(['app/app',
        'tpl!app/modules/events/templates/Layout.tpl'],
function(HoneySens, LayoutTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: 'div.content'
            },
            initialize: function() {
                this.listenTo(HoneySens.vent, 'events:shown', function() {
                    this.$el.find('span.title').html('Ereignisse');
                });
                this.listenTo(HoneySens.vent, 'events:filters:shown', function() {
                    this.$el.find('span.title').html('Ereignisse &rsaquo; Filter');
                });
            }
        });
    });

    return HoneySens.Events.Views.Layout;
});