import HoneySens from 'app/app';
import OverviewTpl from 'app/modules/info/templates/Overview.tpl';

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

export default HoneySens.Info.Views.Overview;