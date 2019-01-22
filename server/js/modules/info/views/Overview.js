define(['app/app',
        'tpl!app/modules/info/templates/Overview.tpl'],
function(HoneySens, OverviewTpl) {
    HoneySens.module('Info.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Overview = Marionette.ItemView.extend({
            template: OverviewTpl,
            className: 'row'
        });
    });

    return HoneySens.Info.Views.Overview;
});