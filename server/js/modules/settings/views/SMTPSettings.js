define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'app/modules/settings/views/ModalSendTestMail',
        'tpl!app/modules/settings/templates/SMTPSettings.tpl',
        'validator'],
function(HoneySens, ModalSettingsSaveView, ModalSendTestMail, SMTPSettingsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.SMTPSettings = Marionette.ItemView.extend({
            template: SMTPSettingsTpl,
            className: 'panel-body',
            submitTestMail: false, // Indicates whether the test mail dialog should be invoked after a form submit event
            events: {
                'click button.saveSettings': function(e) {
                    e.preventDefault();
                    this.submitTestMail = false;
                    this.$el.find('form').trigger('submit');
                },
                'click button.sendTestMail': function(e) {
                    e.preventDefault();
                    this.enableSection();
                    if(this.isFormValid()) {
                        this.submitTestMail = true;
                        this.$el.find('form').trigger('submit');
                    }
                    this.disableSection();
                },
                'click button.reset': function() {
                    this.$el.find('form').trigger('reset');
                }
            },
            onRender: function() {
                var view = this;
                // Set SMTP encryption from model
                this.$el.find('select[name="smtpEncryption"] option[value="' + this.model.get('smtpEncryption') + '"]').prop('selected', true);
                // Submission handler
                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();
                        if(view.submitTestMail) {
                            var smtpModel = new Backbone.Model();
                            smtpModel.set(view.getFormData());
                            HoneySens.request('view:modal').show(new ModalSendTestMail({model: smtpModel}));
                        } else {
                            view.model.save(view.getFormData(), {
                                success: function () {
                                    HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                                }
                            });
                        }
                    }
                });
            },
            isFormValid: function() {
                var $form = this.$el.find('form');
                return !$form.validator('validate').has('.has-error').length;
            },
            enableSection: function() {
                this.$el.find('input[name="smtpServer"], input[name="smtpPort"], input[name="smtpFrom"]')
                    .attr('required', true);
            },
            disableSection: function() {
                this.$el.find('input[name="smtpServer"], input[name="smtpPort"], input[name="smtpFrom"]')
                    .attr('required', false);
            },
            getFormData: function() {
                return {
                    smtpServer: this.$el.find('input[name="smtpServer"]').val(),
                    smtpPort: parseInt(this.$el.find('input[name="smtpPort"]').val()),
                    smtpEncryption: parseInt(this.$el.find('select[name="smtpEncryption"]').val()),
                    smtpFrom: this.$el.find('input[name="smtpFrom"]').val(),
                    smtpUser: this.$el.find('input[name="smtpUser"]').val(),
                    smtpPassword: this.$el.find('input[name="smtpPassword"]').val()
                }
            }
        });
    });

    return HoneySens.Settings.Views.SMTPSettings;
});