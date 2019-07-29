define(['app/app',
        'app/models',
        'tpl!app/templates/Login.tpl'],
function(HoneySens, Models, LoginTpl) {
    HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Login = Marionette.ItemView.extend({
            template: LoginTpl,
            events: {
                'click button.btn-primary': 'login'
            },
            initialize: function() {
                // this event is deprecated
                this.listenTo(HoneySens.vent, 'login:success', function() {
                    this.$el.find('input, button').fadeOut();
                    this.$el.find('div.loginResult.alert-success').fadeIn();
                });
                this.listenTo(HoneySens.vent, 'login:failed', function() {
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
                HoneySens.request('login', {username: username, password: password});
            }
        });
    });

    return HoneySens.Views.Login;
});
