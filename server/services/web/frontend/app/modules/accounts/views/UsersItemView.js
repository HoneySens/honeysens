import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalRemoveUser from 'app/modules/accounts/views/ModalRemoveUser';
import UsersItemViewTpl from 'app/modules/accounts/templates/UsersItemView.tpl';
import UserItemTemplateHelpers from 'app/modules/accounts/views/common';

const UsersItemView = View.extend({
    template: _.template(UsersItemViewTpl),
    tagName: 'tr',
    events: {
        'click button.removeUser': function(e) {
            e.preventDefault();
            radio.request('view:modal').show(new ModalRemoveUser({ model: this.model }));
        },
        'click button.editUser': function(e) {
            e.preventDefault();
            radio.request('accounts:user:edit', this.model, {animation: 'slideLeft'});
        }
    },
    templateContext: {...i18n, ...UserItemTemplateHelpers},
    onRender: function() {
        if(this.model.id == 1) this.$el.addClass('warning');
        this.$el.find('button').tooltip();
    }
});

export default UsersItemView;
