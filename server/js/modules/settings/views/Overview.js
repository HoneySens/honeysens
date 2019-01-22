define(['app/app',
        'app/modules/settings/views/Maintenance',
        'app/modules/settings/views/Settings',
        'tpl!app/modules/settings/templates/Overview.tpl'],
function(HoneySens, MaintenanceView, SettingsView, OverviewTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Overview = Marionette.LayoutView.extend({
            template: OverviewTpl,
            regions: {settings: 'div.settings', maintenance: 'div.maintenance'},
            onRender: function() {
                this.getRegion('settings').show(new SettingsView({model: this.model}));
                this.getRegion('maintenance').show(new MaintenanceView());
            }
        });
    });

    return HoneySens.Settings.Views.Overview;
});