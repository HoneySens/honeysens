define(['app/app', 'app/models',
        'app/modules/accounts/views/DivisionsUserItemView',
        'tpl!app/modules/accounts/templates/DivisionsUserListView.tpl'],
function(HoneySens, Models, DivisionsUserItemView, DivisionsUserListViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        // inline views to render the "add user" dropdown menu
        var DivisionsUserDropdownItem = Marionette.ItemView.extend({
            template: _.template('<a href="#"><%- name %></a>'),
            tagName: 'li',
            events: {
                'click': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:division:user:add', this.model);
                }
            }
        });

        var DivisionsUserDropdownView = Marionette.CollectionView.extend({
            template: false,
            childView: DivisionsUserDropdownItem
        });

        Views.DivisionsUserListView = Marionette.CompositeView.extend({
            template: DivisionsUserListViewTpl,
            childViewContainer: 'tbody',
            childView: DivisionsUserItemView,
            initialize: function() {
                var view = this;
                view.availableUsers = new Models.Users(HoneySens.data.models.users.filter(function(u) {
                    return !this.contains(u);
                }, view.collection));

                HoneySens.reqres.setHandler('accounts:division:user:add', function(user) {
                    view.collection.add(user);
                    view.availableUsers.remove(user);
                });
                HoneySens.reqres.setHandler('accounts:division:user:remove', function(user) {
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
    });

    return HoneySens.Accounts.Views.DivisionsUserListView;
});