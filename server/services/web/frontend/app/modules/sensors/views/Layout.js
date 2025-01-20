define(['app/app',
        'app/views/regions',
        'app/modules/sensors/templates/Layout.tpl',
        'app/views/common'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: _.template(LayoutTpl),
            regions: {
                content: 'div.content'
            }
        });
    });

    return HoneySens.Sensors.Views.Layout;
});