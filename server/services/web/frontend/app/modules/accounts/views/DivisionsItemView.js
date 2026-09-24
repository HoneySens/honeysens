import { radio } from 'app/radio';
import HoneySens from 'app/app';
import DivisionsItemViewTpl from 'app/modules/accounts/templates/DivisionsItemView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.DivisionsItemView = Marionette.View.extend({
        template: _.template(DivisionsItemViewTpl),
        tagName: 'tr',
        events: {
            'click button.remove': function(e) {
                e.preventDefault();
                radio.request('accounts:division:remove', this.model);
            },
            'click button.edit': function(e) {
                e.preventDefault();
                radio.request('accounts:division:edit', this.model, {animation: 'slideLeft'});
            }
        },
        onRender: function() {
            this.$el.find('button').tooltip();
        },
        templateContext: {
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