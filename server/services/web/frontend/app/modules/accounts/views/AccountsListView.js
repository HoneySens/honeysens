import HoneySens from 'app/app';
import UsersListView from 'app/modules/accounts/views/UsersListView';
import DivisionsListView from 'app/modules/accounts/views/DivisionsListView';
import AccountsListViewTpl from 'app/modules/accounts/templates/AccountsListView.tpl';
import 'app/views/common';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.AccountsListView = HoneySens.Views.SlideLayoutView.extend({
        template: _.template(AccountsListViewTpl),
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

export default HoneySens.Accounts.Views.AccountsListView;