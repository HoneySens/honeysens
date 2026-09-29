import _ from 'underscore';
import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import Backbone from 'backbone';
import { radio } from 'app/radio';
import ModalServerError from 'app/common/views/ModalServerError';
import UserPasswordTpl from 'app/modules/setup/templates/UserPassword.tpl';
import 'validator';

const UserPassword = View.extend({
    template: _.template(UserPasswordTpl),
    templateContext: {...i18n},
    errors: {
        2: i18n.t('setup:errorUserPasswordReuse')
    },
    events: {
        'click button:submit': function(e) {
            e.preventDefault();
            this.$el.find('form').trigger('submit');
        },
        'click button.btn-default': function(e) {
            e.preventDefault();
            radio.request('logout');
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
                        view.$el.find('button').prop('disabled', true);
                        setTimeout(() => {
                            radio.request('logout');
                        }, 500);
                    },
                    error: function(xhr) {
                        var modal;
                        if(xhr.status === 403) {
                            modal = {msg: i18n.t('setup:errorSessionExpired'), onClose: function() {
                                radio.request('logout');
                            }};
                        } else {
                            modal = {xhr: xhr, errors: view.errors};
                        }
                        radio.request('view:modal').show(new ModalServerError({model: new Backbone.Model(modal)}));
                        view.$el.find('form').trigger('reset');
                    }
                });
            }
        });
    }
});

export default UserPassword;
