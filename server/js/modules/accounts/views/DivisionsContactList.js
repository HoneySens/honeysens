define(['app/app', 'app/models',
        'app/modules/accounts/views/DivisionsContactItem',
        'tpl!app/modules/accounts/templates/DivisionsContactList.tpl'],
function(HoneySens, Models, DivisionsContactItemView, DivisionsContactListTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.DivisionsContactList = Marionette.CompositeView.extend({
            template: DivisionsContactListTpl,
            childViewContainer: 'tbody',
            childView: DivisionsContactItemView,
            events: {
                'click button.add': function(e) {
                    e.preventDefault();
                    this.collection.add(new Models.IncidentContact());
                }
            },
            initialize: function() {
                var view = this;
                HoneySens.reqres.setHandler('accounts:division:contact:remove', function(contact) {
                    view.collection.remove(contact);
                });
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsContactList;
});