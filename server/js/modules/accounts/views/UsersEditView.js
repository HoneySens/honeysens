define(['app/app',
        'app/models',
        'app/common/views/ModalServerError',
        'tpl!app/modules/accounts/templates/UsersEditView.tpl',
        'app/views/common',
        'validator'],
function(HoneySens, Models, ModalServerError, UsersEditViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.UsersEditView = HoneySens.Views.SlideItemView.extend({
            template: UsersEditViewTpl,
            className: 'transitionView row',
            errors: {
                1: 'Das Login ist bereits im System vorhanden und kann nicht doppelt vergeben werden.'
            },
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
                    var $pwField = this.$el.find('input#password');
                    var $pwConfirmField = this.$el.find('input#confirmPassword');
                    // In case a password was entered, also require the confirmation field
                    $pwConfirmField.attr('required', $pwField.val().length > 0);
                },
                'keyup input#confirmPassword': function(e) {
                    e.preventDefault();
                    this.$el.find('form').validator('destroy');
                    this.$el.find('form').validator('update');
                },
                'change select[name="domain"]': function(e) {
                    this.refreshPasswordFields(e.target.value);
                }
            },
            onRender: function() {
                var view = this;
                this.refreshPasswordFields(this.model.get('domain'));
                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();

                        view.$el.find('button').prop('disabled', true);
                        var model = view.model,
                            modelData = view.getFormData();
                        if(model.id) {
                            // Update an existing user
                            $.ajax({
                                type: 'PUT',
                                url: 'api/users/' + model.id,
                                data: JSON.stringify(modelData),
                                error: function(xhr) {
                                    HoneySens.request('view:modal').show(new ModalServerError({model: new Backbone.Model({xhr: xhr, errors: view.errors})}));
                                    view.$el.find('button').prop('disabled', false);
                                },
                                success: function() {
                                    HoneySens.data.models.users.fetch({ reset: true, success: function() {
                                        if(model.id == HoneySens.data.session.user.id) HoneySens.execute('logout');
                                        HoneySens.request('accounts:show', {animation: 'slideRight'});
                                    }});
                                }
                            });
                        } else {
                            // Create new user
                            $.ajax({
                                type: 'POST',
                                url: 'api/users',
                                data: JSON.stringify(modelData),
                                error: function(xhr) {
                                    HoneySens.request('view:modal').show(new ModalServerError({model: new Backbone.Model({xhr: xhr, errors: view.errors})}));
                                    view.$el.find('button').prop('disabled', false);
                                },
                                success: function(data) {
                                    data = JSON.parse(data);
                                    model.id = data.id;
                                    HoneySens.data.models.users.fetch({ reset: true, success: function() {
                                            HoneySens.request('accounts:show', {animation: 'slideRight'});
                                    }});
                                }
                            });
                        }
                    }
                });
            },
            templateHelpers: {
                isEdit: function() {
                    return typeof this.id !== 'undefined';
                }
            },
            /**
             * Render or hide password entry fields depending on the selected domain.
             * Set validators based on whether we create a new user or edit an existing one.
             */
            refreshPasswordFields: function(domain) {
                var $fields = this.$el.find('div.form-group.password, div.checkbox.requirePasswordChange');
                if(parseInt(domain) === Models.User.domain.LOCAL) {
                    $fields.removeClass('hide');
                    // Require a password for new models or if the current model doesn't use local authentication
                    $fields.find('input').attr('required', this.model.id == null || this.model.get('domain') !== Models.User.domain.LOCAL);
                } else {
                    $fields.find('input').val('');
                    $fields.addClass('hide');
                    $fields.find('input').attr('required', false);
                }
            },
            getFormData: function() {
                var $password = this.$el.find('input[name="password"]'),
                    $fullName = this.$el.find('input[name="fullName"]'),
                    data = {
                        name: this.$el.find('input[name="username"]').val(),
                        domain: parseInt(this.$el.find('select[name="domain"]').val()),
                        email: this.$el.find('input[name="email"]').val(),
                        role: this.$el.find('select[name="role"]').val(),
                        notifyOnCAExpiration: this.$el.find('input[name="notifyOnCAExpiration"]').is(':checked'),
                        requirePasswordChange: this.$el.find('input[name="requirePasswordChange"]').is(':checked')
                    };
                if($password.val().length !== 0) data.password = $password.val();
                if($fullName.val().length !== 0) data.fullName = $fullName.val();
                return data;
            }
        });
    });

    return HoneySens.Accounts.Views.UsersEditView;
});