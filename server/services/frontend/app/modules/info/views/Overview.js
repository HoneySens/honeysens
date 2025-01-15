define(['app/app',
        'app/modules/info/templates/Overview.tpl'],
function(HoneySens, OverviewTpl) {
    HoneySens.module('Info.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Overview = Marionette.ItemView.extend({
            template: _.template(OverviewTpl),
            className: 'row',
            templateHelpers: {
                showBuildID: function() {
                    return HoneySens.data.system.get('build_id');
                }
            }
        });
    });

    return HoneySens.Info.Views.Overview;
});