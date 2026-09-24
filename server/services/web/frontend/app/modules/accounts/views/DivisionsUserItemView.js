import { radio } from 'app/radio';
import HoneySens from 'app/app';
import DivisionsUserItemViewTpl from 'app/modules/accounts/templates/DivisionsUserItemView.tpl';
import 'app/modules/accounts/views/common';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.DivisionsUserItemView = Marionette.View.extend({
        template: _.template(DivisionsUserItemViewTpl),
        tagName: 'tr',
        events: {
            'click button.remove': function(e) {
                e.preventDefault();
                radio.request('accounts:division:user:remove', this.model);
            }
        },
        templateContext: Views.UserItemTemplateHelpers,
        onRender: function() {
            this.$el.find('button').tooltip();
        }
    });
});

export default HoneySens.Accounts.Views.DivisionsUserItemView;