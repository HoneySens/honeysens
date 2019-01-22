define(['app/app',
        'app/modules/accounts/views/UsersItemView',
        'tpl!app/modules/accounts/templates/DivisionsUserItemView.tpl',
        'app/modules/accounts/views/common'],
function(HoneySens, UsersItemView, DivisionsUserItemViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.DivisionsUserItemView = Marionette.ItemView.extend({
            template: DivisionsUserItemViewTpl,
            tagName: 'tr',
            events: {
                'click button.remove': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:division:user:remove', this.model);
                }
            },
            templateHelpers: Views.UserItemTemplateHelpers,
            onRender: function() {
                this.$el.find('button').tooltip();
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsUserItemView;
});