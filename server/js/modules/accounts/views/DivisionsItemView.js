define(['app/app', 'tpl!app/modules/accounts/templates/DivisionsItemView.tpl'],
function(HoneySens, DivisionsItemViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.DivisionsItemView = Marionette.ItemView.extend({
            template: DivisionsItemViewTpl,
            tagName: 'tr',
            events: {
                'click button.remove': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:division:remove', this.model);
                },
                'click button.edit': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:division:edit', this.model, {animation: 'slideLeft'});
                }
            },
            onRender: function() {
                this.$el.find('button').tooltip();
            },
            templateHelpers: {
                getUserCount: function() {
                    return this.users.length;
                },
                getSensorCount: function() {
                    return HoneySens.data.models.sensors.where({division: this.id}).length;
                }
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsItemView;
});