import HoneySens from 'app/app';
import Regions from 'app/views/regions';
import AccountsViewTpl from 'app/modules/accounts/templates/AccountsView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.AccountsView = Marionette.LayoutView.extend({
        template: _.template(AccountsViewTpl),
        regions: { content: { selector: 'div.content', regionClass: Regions.TransitionRegion } },
        initialize: function() {
            this.getRegion('content').concurrentTransition = true;
        }
    });
});

export default HoneySens.Accounts.Views.AccountsView;