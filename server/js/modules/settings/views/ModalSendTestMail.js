define(['app/app',
        'tpl!app/modules/settings/templates/ModalSendTestMail.tpl',
        'validate'],
function(HoneySens, ModalSendTestMailTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalSendTestMail = Marionette.ItemView.extend({
            template: ModalSendTestMailTpl,
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    this.$el.find('form').bootstrapValidator('validate');
                }
            },
            onRender: function() {
                var view = this;
                // Validation
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        recipient: {
                            validators: {
                                notEmpty: {},
                                emailAddress: {}
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    // Temporarily disable the submit button
                    view.$el.find('button.btn-primary').prop('disabled', true);
                    view.model.set('recipient', view.$el.find('input[name="recipient"]').val());
                    view.model.save({}, {
                        success: function() {
                            view.$el.find('div.sendSuccess').removeClass('hidden');
                            view.$el.find('button.btn-primary').prop('disabled', false);
                        },
                        error: function(model, resp) {
                            var errorMsg = 'Unbekannter Fehler';
                            if(resp.hasOwnProperty('responseJSON') && resp.responseJSON.hasOwnProperty('error'))
                                errorMsg = resp.responseJSON.error;
                            view.$el.find('div.sendError code').text(errorMsg);
                            view.$el.find('div.sendError').removeClass('hidden');
                            view.$el.find('button.btn-primary').prop('disabled', false);
                        }
                    });
                });
            }
        });
    });

    return HoneySens.Settings.Views.ModalSendTestMail;
});