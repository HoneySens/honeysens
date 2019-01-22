define(['app/app', 'app/routing', 'app/models',
        'app/modules/accounts/views/AccountsView',
        'app/modules/accounts/views/AccountsListView',
        'app/modules/accounts/views/DivisionsEditView',
        'app/modules/accounts/views/ModalRemoveDivision',
        'app/modules/accounts/views/UsersEditView'],
function(HoneySens, Routing, Models, AccountsView, AccountsListView, DivisionsEditView, ModalRemoveDivision, UsersEditView) {
    var AccountsModule = Routing.extend({
        name: 'accounts',
        startWithParent: false,
        rootView: null,
        menuItems: [
            {title: 'Benutzer u. Gruppen', uri: 'accounts', iconClass: 'glyphicon glyphicon-user', permission: {domain: 'divisions', action: 'create'}, priority: 4}
        ],
        start: function() {
            console.log('Starting module: accounts');
            this.rootView = new AccountsView();
            HoneySens.request('view:content').main.show(this.rootView);

            // register command handlers
            var contentRegion = this.rootView.getRegion('content'),
                router = this.router;

            HoneySens.reqres.setHandler('accounts:show', function(options) {
                if(!HoneySens.assureAllowed('users', 'get')) return false;
                contentRegion.show(new AccountsListView({users: HoneySens.data.models.users, divisions: HoneySens.data.models.divisions}), options);
                router.navigate('accounts');
            });
            HoneySens.reqres.setHandler('accounts:division:add', function(options) {
                if(!HoneySens.assureAllowed('divisions', 'create')) return false;
                contentRegion.show(new DivisionsEditView({model: new Models.Division()}), options);
                router.navigate('accounts/division/add');
            });
            HoneySens.reqres.setHandler('accounts:division:edit', function(division, options) {
                if(!HoneySens.assureAllowed('divisions', 'update')) return false;
                contentRegion.show(new DivisionsEditView({model: division}), options);
                router.navigate('accounts/division/edit/' + division.id);
            });
            HoneySens.reqres.setHandler('accounts:division:remove', function(division) {
                HoneySens.request('view:modal').show(new ModalRemoveDivision({model: division}));
            });
            HoneySens.reqres.setHandler('accounts:user:add', function(options) {
                if(!HoneySens.assureAllowed('users', 'create')) return false;
                contentRegion.show(new UsersEditView({model: new Models.User()}), options);
                router.navigate('accounts/user/add');
            });
            HoneySens.reqres.setHandler('accounts:user:edit', function(user, options) {
                if(!HoneySens.assureAllowed('users', 'update')) return false;
                contentRegion.show(new UsersEditView({model: user}), options);
                router.navigate('accounts/user/edit/' + user.id);
            });
        },
        stop: function() {
            console.log('Stopping module: accounts');
            HoneySens.reqres.removeHandler('accounts:show');
            HoneySens.reqres.removeHandler('accounts:division:add');
            HoneySens.reqres.removeHandler('accounts:division:edit');
            HoneySens.reqres.removeHandler('accounts:division:remove');
            HoneySens.reqres.removeHandler('accounts:user:add');
            HoneySens.reqres.removeHandler('accounts:user:edit');
        },
        routesList: {
            'accounts': 'showAccounts',
            'accounts/division/add': 'addDivision',
            'accounts/division/edit/:id': 'editDivision',
            'accounts/user/add': 'addUser',
            'accounts/user/edit/:id': 'editUser'
        },
        showAccounts: function() { HoneySens.request('accounts:show'); },
        addDivision: function() { HoneySens.request('accounts:division:add'); },
        editDivision: function(id) { HoneySens.request('accounts:division:edit', HoneySens.data.models.divisions.get(id)); },
        addUser: function() { HoneySens.request('accounts:user:add'); },
        editUser: function(id) { HoneySens.request('accounts:user:edit', HoneySens.data.models.users.get(id)); }
    });

    return HoneySens.module('Accounts.Routing', AccountsModule);
});