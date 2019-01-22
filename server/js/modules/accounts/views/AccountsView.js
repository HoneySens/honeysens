define(['app/app', 'app/views/regions', 'tpl!app/modules/accounts/templates/AccountsView.tpl'],
function(HoneySens, Regions, AccountsViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.AccountsView = Marionette.LayoutView.extend({
            template: AccountsViewTpl,
            regions: { content: { selector: 'div.content', regionClass: Regions.TransitionRegion } },
            initialize: function() {
                this.getRegion('content').concurrentTransition = true;
            }
        });
    });

    return HoneySens.Accounts.Views.AccountsView;
});