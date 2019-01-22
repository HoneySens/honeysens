define(['app/app',
        'app/modules/settings/views/ServerEndpoint',
        'app/modules/settings/views/Sensors',
        'app/modules/settings/views/SMTPSettings',
        'tpl!app/modules/settings/templates/Settings.tpl'],
function(HoneySens, ServerEndpointView, SensorsView, SMTPSettingsView, SettingsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Settings = Marionette.LayoutView.extend({
            template: SettingsTpl,
            className: 'col-sm-12',
            regions: {
                endpoint: 'div#settings-endpoint',
                sensors: 'div#settings-sensors',
                smtp: 'div#settings-smtp'
            },
            events: {
                'click div.smtpSettings button.toggle': function(e) {
                    e.preventDefault();
                    var $button = $(e.target);
                    if($button.hasClass('active')) {
                        // Disable SMTP
                        this.model.save({smtpEnabled: false}, {
                            success: function() {
                                $button.button('toggle');
                                $button.button('inactive');
                            }
                        });
                    } else {
                        // Show form if it didn't validate - TODO temporarily disabled until bootstrapValidator is replaced
                        if(this.smtp.currentView.isFormValid()) {
                            // Form valid - enable SMTP
                            this.model.save({smtpEnabled: true}, {
                                success: function() {
                                    $button.button('active');
                                    $button.button('toggle');
                                }
                            });
                        }
                    }
                }
            },
            onRender: function() {
                this.getRegion('endpoint').show(new ServerEndpointView({model: this.model}));
                this.getRegion('sensors').show(new SensorsView({model: this.model}));
                this.getRegion('smtp').show(new SMTPSettingsView({model: this.model}));
                // Bind SMTP button to model
                var $button = this.$el.find('div.smtpSettings button.toggle');
                if(this.model.get('smtpEnabled')) {
                    $button.button('active');
                    $button.button('toggle');
                }
            }
        });
    });

    return HoneySens.Settings.Views.Settings;
});