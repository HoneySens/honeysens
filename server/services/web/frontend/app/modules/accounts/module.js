import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import { Division, User } from 'app/models';
import LayoutView from 'app/modules/accounts/views/Layout';
import AccountsListView from 'app/modules/accounts/views/AccountsListView';
import DivisionsEditView from 'app/modules/accounts/views/DivisionsEditView';
import ModalRemoveDivision from 'app/modules/accounts/views/ModalRemoveDivision';
import UsersEditView from 'app/modules/accounts/views/UsersEditView';

var AccountsModule = createRoutingModule({
    name: 'accounts',
    startWithParent: false,
    rootView: null,
    menuItems: [
        {title: i18n.t('accounts:header'), uri: 'accounts', iconClass: 'glyphicon glyphicon-user', permission: {domain: 'divisions', action: 'create'}, priority: 4}
    ],
    start: function() {
        console.log('Starting module: accounts');
        this.rootView = new LayoutView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('accounts:show', function() {
            if(!HoneySens.assureAllowed('users', 'get')) return false;
            contentRegion.show(new AccountsListView({users: HoneySens.data.models.users, divisions: HoneySens.data.models.divisions}));
        });
        radio.reply('accounts:division:add', function() {
            if(!HoneySens.assureAllowed('divisions', 'create')) return false;
            radio.request('view:content').getRegion('overlay').showOverlay(
                new DivisionsEditView({model: new Division()}),
                'accounts/division/add',
                'accounts',
                router);
        });
        radio.reply('accounts:division:edit', function(division) {
            if(!HoneySens.assureAllowed('divisions', 'update')) return false;
            radio.request('view:content').getRegion('overlay').showOverlay(
                new DivisionsEditView({model: division}),
                'accounts/division/edit/' + division.id,
                'accounts',
                router);
        });
        radio.reply('accounts:division:remove', function(division) {
            radio.request('view:modal').show(new ModalRemoveDivision({model: division}));
        });
        radio.reply('accounts:user:add', function() {
            if(!HoneySens.assureAllowed('users', 'create')) return false;
            radio.request('view:content').getRegion('overlay').showOverlay(
                new UsersEditView({model: new User({ require_password_change: true })}),
                'accounts/user/add',
                'accounts',
                router);
        });
        radio.reply('accounts:user:edit', function(user) {
            if(!HoneySens.assureAllowed('users', 'update')) return false;
            radio.request('view:content').getRegion('overlay').showOverlay(
                new UsersEditView({model: user}),
                'accounts/user/edit/' + user.id,
                'accounts',
                router);
        });
    },
    stop: function() {
        console.log('Stopping module: accounts');
        radio.stopReplying('accounts:show');
        radio.stopReplying('accounts:division:add');
        radio.stopReplying('accounts:division:edit');
        radio.stopReplying('accounts:division:remove');
        radio.stopReplying('accounts:user:add');
        radio.stopReplying('accounts:user:edit');
    },
    routesList: {
        'accounts': 'showAccounts',
        'accounts/division/add': 'addDivision',
        'accounts/division/edit/:id': 'editDivision',
        'accounts/user/add': 'addUser',
        'accounts/user/edit/:id': 'editUser'
    },
    showAccounts: function() {
        radio.request('accounts:show');
        this.router.navigate('accounts');
    },
    addDivision: function() {
        radio.request("accounts:show");
        radio.request('accounts:division:add');
    },
    editDivision: function(id) {
        radio.request("accounts:show");
        radio.request('accounts:division:edit', HoneySens.data.models.divisions.get(id));
    },
    addUser: function() {
        radio.request("accounts:show");
        radio.request('accounts:user:add');
    },
    editUser: function(id) {
        radio.request("accounts:show");
        radio.request('accounts:user:edit', HoneySens.data.models.users.get(id));
    }
});

export default AccountsModule;