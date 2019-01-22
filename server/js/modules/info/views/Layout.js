define(['app/app',
        'app/views/regions',
        'tpl!app/modules/info/templates/Layout.tpl',
        'app/views/regions'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Info.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: 'div.content'
            }
        });
    });

    return HoneySens.Info.Views.Layout;
});