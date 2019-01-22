define(['app/app',
        'tpl!app/modules/accounts/templates/UsersEditView.tpl',
        'sha1',
        'app/views/common',
        'validate'],
function(HoneySens, UsersEditViewTpl, sha1) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.UsersEditView = HoneySens.Views.SlideItemView.extend({
            template: UsersEditViewTpl,
            className: 'transitionView row',
            events: {
                'click button.cancel': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:show', {animation: 'slideRight'});
                },
                'click button.save': function(e) {
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
                        username: {
                            validators: {
                                notEmpty: {},
                                regexp: {
                                    regexp: /^[a-z0-9]+$/i,
                                    message: 'Nur Groß-, Kleinbuchstaben und Zahlen erlaubt'
                                },
                                stringLength: {
                                    min: 1,
                                    max: 255,
                                    message: 'Maximale Länge: 255 Zeichen'
                                }
                            }
                        },
                        password: {
                            validators: {
                                stringLength: {
                                    min: 6,
                                    max: 255,
                                    message: 'Passwortlänge zwischen 6 und 255 Zeichen'
                                },
                                identical: {
                                    field: 'confirmPassword',
                                    message: 'Die Passwörter stimmen nicht überein'
                                }
                            }
                        },
                        confirmPassword: {
                            validators: {
                                stringLength: {
                                    min: 6,
                                    max: 255,
                                    message: 'Passwortlänge zwischen 6 und 255 Zeichen'
                                },
                                identical: {
                                    field: 'password',
                                    message: 'Die Passwörter stimmen nicht überein'
                                }
                            }
                        },
                        email: {
                            validators: {
                                notEmpty: {},
                                emailAddress: {}
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    var name = view.$el.find('input[name="username"]').val(),
                        password = view.$el.find('input[name="password"]').val(),
                        email = view.$el.find('input[name="email"]').val(),
                        role = view.$el.find('select[name="role"]').val(),
                        model = view.model,
                        modelData = { name: name, email: email, role: role };
                    view.$el.find('button').prop('disabled', true);
                    if(model.id) {
                        // update existing user
                        if(password.length > 0) modelData.password = sha1(password);
                        $.ajax({
                            type: 'PUT',
                            url: 'api/users/' + model.id,
                            data: JSON.stringify(modelData),
                            success: function() {
                                HoneySens.data.models.users.fetch({ reset: true, success: function() {
                                    if(model.id == HoneySens.data.session.user.id) HoneySens.execute('logout');
                                    HoneySens.request('accounts:show', {animation: 'slideRight'});
                                }});
                            }
                        });
                    } else {
                        // create new user
                        modelData.password = sha1(password);
                        $.post('api/users', JSON.stringify(modelData), function(data) {
                            data = JSON.parse(data);
                            model.id = data.id;
                            HoneySens.data.models.users.fetch({ reset: true, success: function() {
                                HoneySens.request('accounts:show', {animation: 'slideRight'});
                            }});
                        });
                    }
                });
            },
            templateHelpers: {
                isEdit: function() {
                    return typeof this.id !== 'undefined';
                }
            }
        });
    });

    return HoneySens.Accounts.Views.UsersEditView;
});