import { View } from 'backbone.marionette';
import MaintenanceView from 'app/modules/settings/views/Maintenance';
import SettingsView from 'app/modules/settings/views/Settings';
import OverviewTpl from 'app/modules/settings/templates/Overview.tpl';

const Overview = View.extend({
    template: _.template(OverviewTpl),
    regions: {
        settings: 'div.settings',
        maintenance: 'div.maintenance'
    },
    onRender: function() {
        this.getRegion('settings').show(new SettingsView({model: this.model}));
        this.getRegion('maintenance').show(new MaintenanceView({model: this.model}));
    }
});

export default Overview;
