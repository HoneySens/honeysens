define(['app/app', 'app/models'], function(HoneySens, Models) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.UserItemTemplateHelpers = {
            showRole: function() {
                switch(this.role) {
                    case Models.User.role.OBSERVER:
                        return 'Beobachter';
                        break;
                    case Models.User.role.MANAGER:
                        return 'Manager';
                        break;
                    case Models.User.role.ADMIN:
                        return 'Administrator';
                        break;
                }
            },
            isLoggedIn: function() {
                return this.id == HoneySens.data.session.user.id;
            }
        }
    });
});