define(['app/app',
        'tpl!app/modules/accounts/templates/UsersEditView.tpl',
        'sha1',
        'app/views/common',
        'validator'],
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
                    var valid = true;

                    this.$el.find('form').validator('validate');
                    this.$el.find('form .form-group').each(function() {
                        valid = !$(this).hasClass('has-error') && valid;
                    });

                    if(valid) {
                        this.$el.find('form').trigger('submit');
                        this.$el.find('button').prop('disabled', true);
                    }
                },
                'keyup input#password': function(e) {
                    e.preventDefault();
                    
                    var pwField = this.$el.find('input#password');
                    var pwConfirmField = this.$el.find('input#confirmPassword');
                    if (pwField.val().length > 0) { 
                        if(!pwConfirmField.attr('required')){
                            pwConfirmField.attr('required', true);
                        }
                    } else {
                        pwConfirmField.attr('required', false);
                    }
                },
                'keyup input#confirmPassword': function(e) {
                    e.preventDefault();
                    this.$el.find('form').validator('destroy');
                    this.$el.find('form').validator('update');
                }
            },
            onRender: function() {
                var view = this;

                if (!view.model.id) {
                    view.$el.find('input[name="password"]').attr('required', true);
                    view.$el.find('input[name="confirmPassword"]').attr('required', true);
                }

                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();

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