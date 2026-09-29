import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import AdminPasswordTpl from 'app/modules/setup/templates/AdminPassword.tpl';
import 'validator';

const AdminPassword = View.extend({
    template: _.template(AdminPasswordTpl),
    templateContext: {...i18n},
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
                radio.request('setup:install:show', {step: 2, model: view.model});
            }
        });
    }
});

export default AdminPassword;
