define(['app/app',
        'tpl!app/modules/setup/templates/AdminPassword.tpl',
        'validator'],
function(HoneySens, AdminPasswordTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.AdminPassword = Marionette.ItemView.extend({
            template: AdminPasswordTpl,
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

                        var password = view.$el.find('input[name="adminPassword"]').val();
                        view.model.set({password: password});
                        HoneySens.request('setup:install:show', {step: 2, model: view.model});
                    }
                });
            }
        });
    });

    return HoneySens.Setup.Views.AdminPassword;
});