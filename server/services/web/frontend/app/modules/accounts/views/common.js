import HoneySens from 'app/app';
import Models from 'app/models';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.UserItemTemplateHelpers = {
        showRole: function() {
            switch(this.role) {
                case Models.User.role.OBSERVER:
                    return _.t('accounts:roleObserver');
                    break;
                case Models.User.role.MANAGER:
                    return _.t('accounts:roleManager')
                    break;
                case Models.User.role.ADMIN:
                    return _.t('accounts:roleAdmin')
                    break;
            }
        },
        isLoggedIn: function() {
            return this.id == HoneySens.data.session.user.id;
        }
    }
});