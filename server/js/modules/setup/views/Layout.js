define(['app/app',
        'tpl!app/modules/setup/templates/Layout.tpl'],
function(HoneySens, LayoutTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: {
                    selector: 'div.content'
                }
            }
        });
    });

    return HoneySens.Setup.Views.Layout;
});
