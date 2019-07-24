define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'tpl!app/modules/settings/templates/LDAP.tpl',
        'validator'],
function(HoneySens, ModalSettingsSaveView, LDAPTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.LDAP = Marionette.ItemView.extend({
            template: LDAPTpl,
            className: 'panel-body',
            events: {
                'click button.reset': function() {
                    this.$el.find('form').trigger('reset');
                }
            },
            onRender: function() {
                var view = this;
                // Set LDAP encryption from model
                this.$el.find('select[name="ldapEncryption"] option[value="' + this.model.get('ldapEncryption') + '"]').prop('selected', true);
                // Enable help popovers
                this.$el.find('[data-toggle="popover"]').popover();
                // Submission handler
                this.$el.find('form').validator().on('submit', function (e) {
                    if(!e.isDefaultPrevented()) {
                        e.preventDefault();
                        view.model.save(view.getFormData(), {
                            success: function() {
                                HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                            }
                        });
                    }
                });
            },
            isFormValid: function() {
                var $form = this.$el.find('form');
                return !$form.validator('validate').has('.has-error').length;
            },
            enableSection: function() {
                this.$el.find('input').attr('required', true);
            },
            disableSection: function() {
                this.$el.find('input').attr('required', false);
            },
            getFormData: function() {
                return {
                    ldapServer: this.$el.find('input[name="ldapServer"]').val(),
                    ldapPort: this.$el.find('input[name="ldapPort"]').val(),
                    ldapEncryption: this.$el.find('select[name="ldapEncryption"]').val(),
                    ldapTemplate: this.$el.find('input[name="ldapTemplate"]').val()
                }
            }
        });
    });

    return HoneySens.Settings.Views.LDAP;
});