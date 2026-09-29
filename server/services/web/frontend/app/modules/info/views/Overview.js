import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import HoneySens from 'app/app';
import OverviewTpl from 'app/modules/info/templates/Overview.tpl';

const Overview = View.extend({
    template: _.template(OverviewTpl),
    className: 'row',
    templateContext: {
        ...i18n,
        showBuildID: function() {
            return HoneySens.data.system.get('build_id');
        }
    }
});

export default Overview;
