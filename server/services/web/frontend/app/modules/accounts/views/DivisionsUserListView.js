import i18n from 'app/common/i18n';
import { View, CollectionView } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import { Users } from 'app/models';
import DivisionsUserItemView from 'app/modules/accounts/views/DivisionsUserItemView';
import DivisionsUserListViewTpl from 'app/modules/accounts/templates/DivisionsUserListView.tpl';

// inline views to render the "add user" dropdown menu
var DivisionsUserDropdownItem = View.extend({
    template: _.template('<a href="#"><%- name %></a>'),
    tagName: 'li',
    events: {
        'click': function(e) {
            e.preventDefault();
            radio.request('accounts:division:user:add', this.model);
        }
    }
});

var DivisionsUserDropdownView = CollectionView.extend({
    template: false,
    childView: DivisionsUserDropdownItem
});

const DivisionsUserListView = CollectionView.extend({
    template: _.template(DivisionsUserListViewTpl),
    templateContext: {...i18n},
    childViewContainer: 'tbody',
    childView: DivisionsUserItemView,
    initialize: function() {
        var view = this;
        view.availableUsers = new Users(HoneySens.data.models.users.filter(function(u) {
            return !this.contains(u);
        }, view.collection));

        radio.reply('accounts:division:user:add', function(user) {
            view.collection.add(user);
            view.availableUsers.remove(user);
        });
        radio.reply('accounts:division:user:remove', function(user) {
            view.collection.remove(user);
            view.availableUsers.add(user);
        });
    },
    onRender: function() {
        var dropdownView = new DivisionsUserDropdownView({ el: this.$el.find('ul.dropdown-menu'),
            collection: this.availableUsers });
        dropdownView.render();
    }
});

export default DivisionsUserListView;
