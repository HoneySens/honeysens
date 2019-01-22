define(['app/app',
        'app/modules/accounts/views/UsersListView',
        'app/modules/accounts/views/DivisionsListView',
        'tpl!app/modules/accounts/templates/AccountsListView.tpl',
        'app/views/common'],
function(HoneySens, UsersListView, DivisionsListView, AccountsListViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.AccountsListView = HoneySens.Views.SlideLayoutView.extend({
            template: AccountsListViewTpl,
            className: 'transitionView row',
            regions: { users: { selector: 'div.users'}, divisions: { selector: 'div.divisions' } },
            initialize: function(options) {
                this.users = options.users;
                this.divisions = options.divisions;
            },
            onRender: function() {
                this.getRegion('users').show(new UsersListView({ collection: this.users }));
                this.getRegion('divisions').show(new DivisionsListView({ collection: this.divisions }));
            }
        });
    });

    return HoneySens.Accounts.Views.AccountsListView;
});