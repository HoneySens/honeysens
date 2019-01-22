define(['app/app',
        'app/views/regions',
        'tpl!app/modules/platforms/templates/Layout.tpl',
        'app/views/regions'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: 'div.content'
            }
        });
    });

    return HoneySens.Platforms.Views.Layout;
});