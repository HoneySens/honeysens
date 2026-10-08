import i18n from 'app/common/i18n';
import Backbone from 'backbone';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import { IncidentContacts } from 'app/models';
import ModalServerError from 'app/common/views/ModalServerError';
import DivisionsUserListView from 'app/modules/accounts/views/DivisionsUserListView';
import DivisionsContactListView from 'app/modules/accounts/views/DivisionsContactListView';
import DivisionsEditViewTpl from 'app/modules/accounts/templates/DivisionsEditView.tpl';
import 'validator';

const DivisionsEditView = View.extend({
    template: _.template(DivisionsEditViewTpl),
    templateContext: {
        ...i18n,
        isNew: function() {
            return !this.hasOwnProperty('id');
        }
    },
    className: 'container-fluid',
    errors: {
        1: i18n.t('accounts:groupNameConflict')
    },
    regions: {
        users: 'div.userList',
        contacts: 'div.contactList'
    },
    events: {
        'click button.cancel': function() {
            this.trigger('view:close');
        },
        'click button.save': function(e) {
            e.preventDefault();
            var valid = true,
                view = this;

            this.$el.find('form').validator('validate');
            this.$el.find('form .form-group').each(function() {
                valid = !$(this).hasClass('has-error') && valid;
            });

            if(valid) {
                this.$el.find('form').trigger('submit');
                this.$el.find('button').prop('disabled', true);

                var model = this.model,
                    name = this.$el.find('input[name="divisionname"]').val(),
                    users = this.getRegion('users').currentView.collection.pluck("id"),
                    contacts = this.getRegion('contacts').currentView.collection;

                if(!model.id) HoneySens.data.models.divisions.add(model);
                model.save({name: name, users: users, contacts: contacts.toJSON()}, {
                    error: function(model, xhr) {
                        radio.request('view:modal').show(new ModalServerError({model: new Backbone.Model({xhr: xhr, errors: view.errors})}));
                        view.$el.find('button').prop('disabled', false);
                    },
                    success: function() {
                        radio.request('fetchUpdates', false);
                        view.trigger('view:close');
                    }});
            }

        }
    },
    initialize: function() {
        var view = this;
        this.contactCollection = new IncidentContacts();
        this.userCollection = this.model.getUserCollection();
        if(this.model.id) {
            this.contactCollection.reset(HoneySens.data.models.contacts.where({division: this.model.id}));
        }
        radio.reply('accounts:division:users', function() {
            return view.userCollection;
        });
    },
    onRender: function() {
        var view = this;

        this.$el.find('form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
            }
        });

        this.getRegion('users').show(new DivisionsUserListView({collection: view.userCollection}));
        this.getRegion('contacts').show(new DivisionsContactListView({collection: view.contactCollection}));
    }
});

export default DivisionsEditView;
