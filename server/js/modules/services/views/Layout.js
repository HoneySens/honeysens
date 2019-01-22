define(['app/app',
        'app/views/regions',
        'tpl!app/modules/services/templates/Layout.tpl',
        'app/views/regions'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: 'div.content'
            }
        });
    });

    return HoneySens.Services.Views.Layout;
});
