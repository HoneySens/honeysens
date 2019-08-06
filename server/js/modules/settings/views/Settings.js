define(['app/app',
        'app/modules/settings/views/ServerEndpoint',
        'app/modules/settings/views/Sensors',
        'app/modules/settings/views/Permissions',
        'app/modules/settings/views/LDAP',
        'app/modules/settings/views/SMTPSettings',
        'tpl!app/modules/settings/templates/Settings.tpl'],
function(HoneySens, ServerEndpointView, SensorsView, PermissionsView, LDAPView, SMTPSettingsView, SettingsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Settings = Marionette.LayoutView.extend({
            template: SettingsTpl,
            className: 'col-sm-12',
            regions: {
                endpoint: 'div#settings-endpoint',
                sensors: 'div#settings-sensors',
                permissions: 'div#settings-permissions',
                ldap: 'div#settings-ldap',
                smtp: 'div#settings-smtp'
            },
            events: {
                'click div.ldapSettings button.toggle': function(e) {
                    e.preventDefault();
                    this.handleStatusButton($(e.target), this.$el.find(this.regions.ldap), 'ldapEnabled', this.ldap.currentView);
                },
                'click div.smtpSettings button.toggle': function(e) {
                    e.preventDefault();
                    this.handleStatusButton($(e.target), this.$el.find(this.regions.smtp), 'smtpEnabled', this.smtp.currentView);
                }
            },
            onRender: function() {
                this.getRegion('endpoint').show(new ServerEndpointView({model: this.model}));
                this.getRegion('sensors').show(new SensorsView({model: this.model}));
                this.getRegion('permissions').show(new PermissionsView({model: this.model}));
                this.getRegion('ldap').show(new LDAPView({model: this.model}));
                this.getRegion('smtp').show(new SMTPSettingsView({model: this.model}));
                // Bind SMTP button to model
                var $smtpButton = this.$el.find('div.smtpSettings button.toggle');
                if(this.model.get('smtpEnabled')) {
                    $smtpButton.button('active');
                    $smtpButton.button('toggle');
                }
                // Bind LDAP button to model
                var $ldapButton = this.$el.find('div.ldapSettings button.toggle');
                if(this.model.get('ldapEnabled')) {
                    $ldapButton.button('active');
                    $ldapButton.button('toggle');
                }
            },
            handleStatusButton: function($button, $content, property, sectionView) {
                var data = {};
                if($button.hasClass('active')) {
                    // Disable section
                    sectionView.disableSection();
                    if(sectionView.isFormValid()) {
                        data = sectionView.getFormData();
                        data[property] = false;
                        this.model.save(data, {
                            success: function() {
                                $button.button('toggle');
                                $button.button('inactive');
                            }
                        });
                    } else sectionView.enableSection();
                } else {
                    // Enable section
                    sectionView.enableSection();
                    if(sectionView.isFormValid()) {
                        data = sectionView.getFormData();
                        data[property] = true;
                        this.model.save(data, {
                            success: function() {
                                $button.button('active');
                                $button.button('toggle');
                            }
                        });
                    } else {
                        // Visible collapsible panels have the class 'in' set on them
                        if(!$content.hasClass('in')) $content.collapse('toggle');
                        sectionView.disableSection();
                    }
                }
            }
        });
    });

    return HoneySens.Settings.Views.Settings;
});