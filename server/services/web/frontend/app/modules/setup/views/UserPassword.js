import HoneySens from 'app/app';
import ModalServerError from 'app/common/views/ModalServerError';
import UserPasswordTpl from 'app/modules/setup/templates/UserPassword.tpl';
import 'validator';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.UserPassword = Marionette.ItemView.extend({
        template: _.template(UserPasswordTpl),
        errors: {
            2: 'Das derzeitige Passwort kann nicht erneut verwendet werden, bitte vergeben Sie ein neues.'
        },
        events: {
            'click button:submit': function(e) {
                e.preventDefault();
                this.$el.find('form').trigger('submit');
            },
            'click button.btn-default': function(e) {
                e.preventDefault();
                HoneySens.execute('logout');
            }
        },
        onRender: function() {
            var view = this;

            this.$el.find('form').validator().on('submit', function (e) {
                if (!e.isDefaultPrevented()) {
                    e.preventDefault();
                    $.ajax({
                        method: 'PUT',
                        dataType: 'json',
                        data: JSON.stringify({password: view.$el.find('input[name="userPassword"]').val()}),
                        contentType: 'application/json',
                        url: 'api/users/session',
                        success: function() {
                            HoneySens.execute('logout');
                        },
                        error: function(xhr) {
                            var modal;
                            if(xhr.status === 403) {
                                modal = {msg: 'Session abgelaufen, bitte erneut anmelden.', onClose: function() {
                                    HoneySens.execute('logout');
                                }};
                            } else {
                                modal = {xhr: xhr, errors: view.errors};
                            }
                            HoneySens.request('view:modal').show(new ModalServerError({model: new Backbone.Model(modal)}));
                            view.$el.find('form').trigger('reset');
                        }
                    });
                }
            });
        }
    });
});

export default HoneySens.Setup.Views.UserPassword;