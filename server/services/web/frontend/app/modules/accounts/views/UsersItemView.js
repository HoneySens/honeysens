import HoneySens from 'app/app';
import ModalRemoveUser from 'app/modules/accounts/views/ModalRemoveUser';
import UsersItemViewTpl from 'app/modules/accounts/templates/UsersItemView.tpl';
import 'app/modules/accounts/views/common';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.UsersItemView = Marionette.ItemView.extend({
        template: _.template(UsersItemViewTpl),
        tagName: 'tr',
        events: {
            'click button.removeUser': function(e) {
                e.preventDefault();
                HoneySens.request('view:modal').show(new ModalRemoveUser({ model: this.model }));
            },
            'click button.editUser': function(e) {
                e.preventDefault();
                HoneySens.request('accounts:user:edit', this.model, {animation: 'slideLeft'});
            }
        },
        templateHelpers: Views.UserItemTemplateHelpers,
        onRender: function() {
            if(this.model.id == 1) this.$el.addClass('warning');
            this.$el.find('button').tooltip();
        }
    });
});

export default HoneySens.Accounts.Views.UsersItemView;