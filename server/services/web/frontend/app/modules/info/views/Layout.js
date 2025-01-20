define(['app/app',
        'app/views/regions',
        'app/modules/info/templates/Layout.tpl',
        'app/views/regions'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Info.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: _.template(LayoutTpl),
            regions: {
                content: 'div.content'
            },
            initialize: function() {
                this.listenTo(HoneySens.vent, 'info:shown', function() {
                    this.$el.find('span.title').html('HoneySens');
                });
            }
        });
    });

    return HoneySens.Info.Views.Layout;
});
