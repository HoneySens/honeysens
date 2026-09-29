import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import DivisionsUserItemViewTpl from 'app/modules/accounts/templates/DivisionsUserItemView.tpl';
import UserItemTemplateHelpers from 'app/modules/accounts/views/common';

const DivisionsUserItemView = View.extend({
    template: _.template(DivisionsUserItemViewTpl),
    tagName: 'tr',
    events: {
        'click button.remove': function(e) {
            e.preventDefault();
            radio.request('accounts:division:user:remove', this.model);
        }
    },
    templateContext: {...i18n, ...UserItemTemplateHelpers},
    onRender: function() {
        this.$el.find('button').tooltip();
    }
});

export default DivisionsUserItemView;
