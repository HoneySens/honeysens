import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import Regions from 'app/views/regions';
import AccountsViewTpl from 'app/modules/accounts/templates/AccountsView.tpl';

const AccountsView = View.extend({
    template: _.template(AccountsViewTpl),
    templateContext: {...i18n},
    regions: {
        content: { el: 'div.content', regionClass: Regions.TransitionRegion }
    },
    initialize: function() {
        this.getRegion('content').concurrentTransition = true;
    }
});

export default AccountsView;
