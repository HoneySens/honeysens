import HoneySens from 'app/app';
import MaintenanceView from 'app/modules/settings/views/Maintenance';
import SettingsView from 'app/modules/settings/views/Settings';
import OverviewTpl from 'app/modules/settings/templates/Overview.tpl';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Overview = Marionette.LayoutView.extend({
        template: _.template(OverviewTpl),
        regions: {settings: 'div.settings', maintenance: 'div.maintenance'},
        onRender: function() {
            this.getRegion('settings').show(new SettingsView({model: this.model}));
            this.getRegion('maintenance').show(new MaintenanceView({model: this.model}));
        }
    });
});

export default HoneySens.Settings.Views.Overview;