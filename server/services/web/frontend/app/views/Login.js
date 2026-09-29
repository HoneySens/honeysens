import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import LoginTpl from 'app/templates/Login.tpl';

const Login = View.extend({
    template: _.template(LoginTpl),
    templateContext: {...i18n},
    events: {
        'click button.btn-primary': 'login'
    },
    initialize: function() {
        // this event is deprecated
        this.listenTo(radio, 'login:success', function() {
            this.$el.find('input, button').fadeOut();
            this.$el.find('div.loginResult.alert-success').fadeIn();
        });
        this.listenTo(radio, 'login:failed', function() {
            this.$el.find('div.loginResult.alert-danger').fadeIn();
        });
    },
    onRender: function() {
        this.$el.find('div.loginResult').hide();
    },
    login: function(e) {
        e.preventDefault();
        var username = this.$el.find('input.username').val(),
            password = this.$el.find(':password').val();
        this.$el.find('div.loginResult.alert').hide();
        radio.request('login', {username: username, password: password});
    }
});

export default Login;
