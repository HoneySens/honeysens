define(['app/app',
        'tpl!app/modules/setup/templates/AdminPassword.tpl',
        'validate'],
function(HoneySens, AdminPasswordTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.AdminPassword = Marionette.ItemView.extend({
            template: AdminPasswordTpl,
            events: {
                'click button:submit': function(e) {
                    e.preventDefault();
                    this.$el.find('form').bootstrapValidator('validate');
                }
            },
            onRender: function() {
                var view = this;
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        adminPassword: {
                            validators: {
                                notEmpty: {},
                                stringLength: {
                                    min: 6,
                                    max: 255
                                }
                            }
                        },
                        adminPasswordRepeat: {
                            validators: {
                                notEmpty: {},
                                stringLength: {
                                    min: 6,
                                    max: 255
                                },
                                identical: {field: 'adminPassword', message: 'Passwörter müssen identisch sein'}
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var password = view.$el.find('input[name="adminPassword"]').val();
                    view.model.set({password: password});
                    HoneySens.request('setup:install:show', {step: 2, model: view.model});
                });
            }
        });
    });

    return HoneySens.Setup.Views.AdminPassword;
});