define(['app/app', 'app/models',
        'app/modules/accounts/views/ModalEditUser',
        'app/modules/accounts/views/UsersItemView',
        'tpl!app/modules/accounts/templates/UsersListView.tpl'],
function(HoneySens, Models, ModalEditUser, UsersItemView, UsersListViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.UsersListView = Marionette.CompositeView.extend({
            template: UsersListViewTpl,
            childViewContainer: 'tbody',
            childView: Views.UsersItemView,
            events: {
                'click #addUser': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:user:add', {animation: 'slideLeft'});
                }
            }
        });
    });

    return HoneySens.Accounts.Views.UsersListView;
});