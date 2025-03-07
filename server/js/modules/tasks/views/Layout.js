define(['app/app',
        'app/views/regions',
        'tpl!app/modules/tasks/templates/Layout.tpl',
        'app/views/common'],
function(HoneySens, Regions, LayoutTpl) {
    HoneySens.module('Tasks.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Layout = Marionette.LayoutView.extend({
            template: LayoutTpl,
            regions: {
                content: 'div.content'
            }
        })
    });

    return HoneySens.Tasks.Views.Layout;
});