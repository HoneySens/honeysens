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
            validateOnly: false, // Indication for the form submission handler to not do anything after validation
            events: {
                'click button.sendTestMail': function(e) {
                    e.preventDefault();
                    this.submitTestMail = true;
                    this.$el.find('form.serverConfig').trigger('submit');
                },
                'click button.saveSettings': function(e) {
                    e.preventDefault();
                    this.submitTestMail = false;
                    this.$el.find('form.serverConfig').trigger('submit');
                }
            },
            onRender: function() {
                var view = this;
                
                this.$el.find('form.serverConfig').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();
                        
                        // Stop here if we nothing else but the validation result was requested
                        if(view.validateOnly) {
                            view.validateOnly = false;
                            return;
                        }
                        var smtpServer = view.$el.find('input[name="smtpServer"]').val(),
                            smtpPort = view.$el.find('input[name="smtpPort"]').val(),
                            smtpFrom = view.$el.find('input[name="smtpFrom"]').val(),
                            smtpUser = view.$el.find('input[name="smtpUser"]').val(),
                            smtpPassword = view.$el.find('input[name="smtpPassword"]').val(),
                            changedAttributes = {smtpServer: smtpServer, smtpPort: smtpPort, smtpFrom: smtpFrom, smtpUser: smtpUser, smtpPassword: smtpPassword};
                        if(view.submitTestMail) {
                            var smtpModel = new Backbone.Model();
                            smtpModel.url = function() {return 'api/settings/testmail'};
                            smtpModel.set(changedAttributes);
                            smtpModel.set('smtpPassword', smtpPassword);
                            HoneySens.request('view:modal').show(new ModalSendTestMail({model: smtpModel}));
                        } else {
                            view.model.save(changedAttributes, {
                                success: function () {
                                    HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                                }
                            });
                        }
                    }
                });
            },
            isFormValid: function() {
                var $form = this.$el.find('form.serverConfig');
                // TODO this is a workaround because bootstrapValidator returns a valid form in case it's collapsed
                // In the future the form should stay collapsed if it's valid.
                $('#settings-smtp').collapse('show');
                this.validateOnly = true;
                $form.validator('validate');
                return $form.data('bootstrapValidator').isValid();
            }
        });
    });

    return HoneySens.Settings.Views.SMTPSettings;
});