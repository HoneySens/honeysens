import UsersListView from 'app/modules/accounts/views/UsersListView';
import DivisionsListView from 'app/modules/accounts/views/DivisionsListView';
import AccountsListViewTpl from 'app/modules/accounts/templates/AccountsListView.tpl';
import { SlideLayoutView } from 'app/views/common';

const AccountsListView = SlideLayoutView.extend({
    template: _.template(AccountsListViewTpl),
    className: 'transitionView row',
    regions: {
        users: { el: 'div.users'},
        divisions: { el: 'div.divisions' }
    },
    initialize: function(options) {
        this.users = options.users;
        this.divisions = options.divisions;
    },
    onRender: function() {
        this.getRegion('users').show(new UsersListView({ collection: this.users }));
        this.getRegion('divisions').show(new DivisionsListView({ collection: this.divisions }));
    }
});

export default AccountsListView;
