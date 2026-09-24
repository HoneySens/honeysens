import { radio } from 'app/radio';
import HoneySens from 'app/app';
import Models from 'app/models';
import UsersItemView from 'app/modules/accounts/views/UsersItemView';
import UsersListViewTpl from 'app/modules/accounts/templates/UsersListView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.UsersListView = Marionette.CollectionView.extend({
        template: _.template(UsersListViewTpl),
        childViewContainer: 'tbody',
        childView: Views.UsersItemView,
        events: {
            'click #addUser': function(e) {
                e.preventDefault();
                radio.request('accounts:user:add', {animation: 'slideLeft'});
            }
        }
    });
});

export default HoneySens.Accounts.Views.UsersListView;