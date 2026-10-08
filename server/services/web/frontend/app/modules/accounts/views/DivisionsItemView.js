import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import DivisionsItemViewTpl from 'app/modules/accounts/templates/DivisionsItemView.tpl';

const DivisionsItemView = View.extend({
    template: _.template(DivisionsItemViewTpl),
    tagName: 'tr',
    events: {
        'click button.remove': function(e) {
            e.preventDefault();
            radio.request('accounts:division:remove', this.model);
        },
        'click button.edit': function(e) {
            e.preventDefault();
            radio.request('accounts:division:edit', this.model);
        }
    },
    onRender: function() {
        this.$el.find('button').tooltip();
    },
    templateContext: {
        ...i18n,
        getUserCount: function() {
            return this.users.length;
        },
        getSensorCount: function() {
            return HoneySens.data.models.sensors.where({division: this.id}).length;
        }
    }
});

export default DivisionsItemView;
