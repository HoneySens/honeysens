define(['app/app',
        'app/models',
        'app/modules/settings/views/ServerEndpoint',
        'app/modules/settings/views/Sensors',
        'app/modules/settings/views/EventArchive',
        'app/modules/settings/views/Permissions',
        'app/modules/settings/views/Logging',
        'app/modules/settings/views/LDAP',
        'app/modules/settings/views/SMTPSettings',
        'app/modules/settings/views/SMTPTemplates',
        'app/modules/settings/views/EventForwarding',
        'tpl!app/modules/settings/templates/Settings.tpl'],
function(HoneySens, Models, ServerEndpointView, SensorsView, EventArchiveView, PermissionsView, LoggingView, LDAPView, SMTPSettingsView, SMTPTemplatesView, EventForwardingView, SettingsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Settings = Marionette.LayoutView.extend({
            template: SettingsTpl,
            className: 'col-sm-12',
            regions: {
                endpoint: 'div#settings-endpoint',
                sensors: 'div#settings-sensors',
                archive: 'div#settings-archive',
                permissions: 'div#settings-permissions',
                logging: 'div#settings-logging',
                ldap: 'div#settings-ldap',
                smtp: 'div#settings-smtp',
                smtpTemplates: 'div#settings-smtp-templates',
                evforward: 'div#settings-evforward'
            },
            templates: new Models.Templates(),
            events: {
                'click div.ldapSettings button.toggle': function(e) {
                    e.preventDefault();
                    this.handleStatusButton($(e.target), this.$el.find(this.regions.ldap), 'ldapEnabled', this.ldap.currentView);
                },
                'click div.smtpSettings button.toggle': function(e) {
                    e.preventDefault();
                    this.handleStatusButton($(e.target), this.$el.find(this.regions.smtp), 'smtpEnabled', this.smtp.currentView);
                },
                'click div.evforwardSettings button.toggle': function(e) {
                    e.preventDefault();
                    this.handleStatusButton($(e.target), this.$el.find(this.regions.evforward), 'syslogEnabled', this.evforward.currentView);
                },
                'show.bs.collapse #settings-smtp-templates': function(e) {
                    this.templates.fetch();
                }
            },
            onRender: function() {
                this.getRegion('endpoint').show(new ServerEndpointView({model: this.model}));
                this.getRegion('sensors').show(new SensorsView({model: this.model}));
                this.getRegion('archive').show(new EventArchiveView({model: this.model}));
                this.getRegion('permissions').show(new PermissionsView({model: this.model}));
                this.getRegion('logging').show(new LoggingView({model: this.model}));
                this.getRegion('ldap').show(new LDAPView({model: this.model}));
                this.getRegion('smtp').show(new SMTPSettingsView({model: this.model}));
                this.getRegion('smtpTemplates').show(new SMTPTemplatesView({collection: this.templates}));
                this.getRegion('evforward').show(new EventForwardingView({model: this.model}));
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
                // Bind EventForwarding button to model
                var $evforwardButton = this.$el.find('div.evforwardSettings button.toggle');
                if(this.model.get('syslogEnabled')) {
                    $evforwardButton.button('active');
                    $evforwardButton.button('toggle');
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