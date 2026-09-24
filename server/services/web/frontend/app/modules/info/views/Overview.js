import HoneySens from 'app/app';
import OverviewTpl from 'app/modules/info/templates/Overview.tpl';

HoneySens.module('Info.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Overview = Marionette.View.extend({
        template: _.template(OverviewTpl),
        className: 'row',
        templateContext: {
            showBuildID: function() {
                return HoneySens.data.system.get('build_id');
            }
        }
    });
});

export default HoneySens.Info.Views.Overview;