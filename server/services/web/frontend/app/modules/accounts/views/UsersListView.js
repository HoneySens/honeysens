import i18n from 'app/common/i18n';
import { CollectionView } from 'backbone.marionette';
import { radio } from 'app/radio';
import UsersItemView from 'app/modules/accounts/views/UsersItemView';
import UsersListViewTpl from 'app/modules/accounts/templates/UsersListView.tpl';

const UsersListView = CollectionView.extend({
    template: _.template(UsersListViewTpl),
    templateContext: {...i18n},
    childViewContainer: 'tbody',
    childView: UsersItemView,
    events: {
        'click #addUser': function(e) {
            e.preventDefault();
            radio.request('accounts:user:add');
        }
    }
});

export default UsersListView;
