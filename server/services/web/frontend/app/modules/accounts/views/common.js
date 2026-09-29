import i18n from 'app/common/i18n';
import HoneySens from 'app/app';
import { UserRole } from 'app/models';

const UserItemTemplateHelpers = {
    showRole: function() {
        switch(this.role) {
            case UserRole.OBSERVER:
                return i18n.t('accounts:roleObserver');
                break;
            case UserRole.MANAGER:
                return i18n.t('accounts:roleManager')
                break;
            case UserRole.ADMIN:
                return i18n.t('accounts:roleAdmin')
                break;
        }
    },
    isLoggedIn: function() {
        return this.id == HoneySens.data.session.user.id;
    }
};

export default UserItemTemplateHelpers;
