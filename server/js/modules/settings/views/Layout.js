define(['app/app',
        'tpl!app/modules/settings/templates/Layout.tpl',
        'app/views/common'],
function(HoneySens, LayoutTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {content: 'div.content'}
        });
    });

    return HoneySens.Settings.Views.Layout;
});
