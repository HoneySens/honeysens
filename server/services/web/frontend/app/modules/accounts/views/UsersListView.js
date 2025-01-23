import HoneySens from 'app/app';
import Models from 'app/models';
import UsersItemView from 'app/modules/accounts/views/UsersItemView';
import UsersListViewTpl from 'app/modules/accounts/templates/UsersListView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.UsersListView = Marionette.CompositeView.extend({
        template: _.template(UsersListViewTpl),
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

export default HoneySens.Accounts.Views.UsersListView;