define(['app/app',
        'app/views/regions',
        'tpl!app/modules/dashboard/templates/Layout.tpl',
        'app/views/common'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: {
                    selector: 'div.content',
                    regionClass: Regions.TransitionRegion
            }},
            initialize: function() {
                this.getRegion('content').concurrentTransition = true;
            }
        });
    });

    return HoneySens.Dashboard.Views.Layout;
});