import i18n from 'app/common/i18n';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import { Division, User } from 'app/models';
import AccountsView from 'app/modules/accounts/views/AccountsView';
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
        this.rootView = new AccountsView();
        radio.request('view:content').getRegion('main').show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('accounts:show', function(options) {
            if(!HoneySens.assureAllowed('users', 'get')) return false;
            contentRegion.show(new AccountsListView({users: HoneySens.data.models.users, divisions: HoneySens.data.models.divisions}), options);
            router.navigate('accounts');
        });
        radio.reply('accounts:division:add', function(options) {
            if(!HoneySens.assureAllowed('divisions', 'create')) return false;
            contentRegion.show(new DivisionsEditView({model: new Division()}), options);
            router.navigate('accounts/division/add');
        });
        radio.reply('accounts:division:edit', function(division, options) {
            if(!HoneySens.assureAllowed('divisions', 'update')) return false;
            contentRegion.show(new DivisionsEditView({model: division}), options);
            router.navigate('accounts/division/edit/' + division.id);
        });
        radio.reply('accounts:division:remove', function(division) {
            radio.request('view:modal').show(new ModalRemoveDivision({model: division}));
        });
        radio.reply('accounts:user:add', function(options) {
            if(!HoneySens.assureAllowed('users', 'create')) return false;
            contentRegion.show(new UsersEditView({model: new User({
                    require_password_change: true
                })}), options);
            router.navigate('accounts/user/add');
        });
        radio.reply('accounts:user:edit', function(user, options) {
            if(!HoneySens.assureAllowed('users', 'update')) return false;
            contentRegion.show(new UsersEditView({model: user}), options);
            router.navigate('accounts/user/edit/' + user.id);
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
    showAccounts: function() { radio.request('accounts:show'); },
    addDivision: function() { radio.request('accounts:division:add'); },
    editDivision: function(id) { radio.request('accounts:division:edit', HoneySens.data.models.divisions.get(id)); },
    addUser: function() { radio.request('accounts:user:add'); },
    editUser: function(id) { radio.request('accounts:user:edit', HoneySens.data.models.users.get(id)); }
});

export default AccountsModule;