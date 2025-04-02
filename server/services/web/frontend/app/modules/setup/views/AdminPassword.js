import HoneySens from 'app/app';
import AdminPasswordTpl from 'app/modules/setup/templates/AdminPassword.tpl';
import 'validator';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.AdminPassword = Marionette.ItemView.extend({
        template: _.template(AdminPasswordTpl),
        events: {
            'click button:submit': function(e) {
                e.preventDefault();
                this.$el.find('form').trigger('submit');
            }
        },
        onRender: function() {
            var view = this;

            this.$el.find('form').validator().on('submit', function (e) {
                if (!e.isDefaultPrevented()) {
                    e.preventDefault();

                    let email = view.$el.find('input[name="adminEmail"]').val(),
                        password = view.$el.find('input[name="adminPassword"]').val();
                    view.model.set({email: email, password: password});
                    HoneySens.request('setup:install:show', {step: 2, model: view.model});
                }
            });
        }
    });
});

export default HoneySens.Setup.Views.AdminPassword;