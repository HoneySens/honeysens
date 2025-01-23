import HoneySens from 'app/app';
import DivisionsItemViewTpl from 'app/modules/accounts/templates/DivisionsItemView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.DivisionsItemView = Marionette.ItemView.extend({
        template: _.template(DivisionsItemViewTpl),
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

export default HoneySens.Accounts.Views.DivisionsItemView;