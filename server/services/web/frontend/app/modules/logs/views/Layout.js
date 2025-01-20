define(['app/app',
        'app/views/regions',
        'app/modules/logs/templates/Layout.tpl'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Logs.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: _.template(LayoutTpl),
            regions: {
                content: 'div.content'
            }
        })
    });

    return HoneySens.Logs.Views.Layout;
});