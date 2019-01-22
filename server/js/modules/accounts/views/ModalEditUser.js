define(['app/app', 'json', 'tpl!app/modules/accounts/templates/ModalEditUser.tpl', 'sha1', 'validate'],
function(HoneySens, JSON, sha1, ModalEditUserTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalEditUser = Marionette.ItemView.extend({
            template: ModalEditUserTpl,
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
                        username: { validators: { notEmpty: {}, regexp: { regexp: /^[a-z0-9]+$/i, message: 'Nur Groß-, Kleinbuchstaben und Zahlen erlaubt' }}},
                        password: { validators: {}}
                    }
                }).on('success.form.bv', function(e) {
                    var name = view.$el.find('input[name="username"]').val(),
                        password = view.$el.find('input[name="password"]').val(),
                        role = view.$el.find('select[name="role"]').val(),
                        model = view.model,
                        modelData = { name: name, role: role };
                    view.$el.find('button:submit').prop('disabled', true);
                    if(model.id) {
                        if(password.length > 0) modelData.password = sha1(password);
                        $.ajax({
                            type: 'PUT',
                            url: 'api/users/' + model.id,
                            data: JSON.stringify(modelData),
                            success: function() {
                                HoneySens.data.models.users.fetch({ reset: true, success: function() {
                                    HoneySens.request('view:modal').empty();
                                    // current logged-in user was edited? log out!
                                    if(model.id == HoneySens.data.session.user.id) HoneySens.router.navigate('logout', { trigger: true });
                                }});
                            }
                        });
                    } else {
                        modelData.password = sha1(password);
                        $.post('api/users', JSON.stringify(modelData), function(data) {
                            data = JSON.parse(data);
                            model.id = data.id;
                            HoneySens.data.models.users.fetch({ reset: true, success: function() {
                                HoneySens.request('view:modal').empty();
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

    return HoneySens.Accounts.Views.ModalEditUser;
});