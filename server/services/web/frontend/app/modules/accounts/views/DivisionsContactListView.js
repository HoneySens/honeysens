import HoneySens from 'app/app';
import Models from 'app/models';
import DivisionsContactItemTpl from 'app/modules/accounts/templates/DivisionsContactItem.tpl';
import DivisionsContactListViewTpl from 'app/modules/accounts/templates/DivisionsContactListView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    var DivisionsContactUserSelectItem = Marionette.ItemView.extend({
        template: _.template('<%- name %>'),
        tagName: 'option',
        onRender: function() {
            this.$el.attr('value', this.model.id);
        }
    });

    var DivisionsContactUserSelect = Marionette.CollectionView.extend({
        template: false,
        childView: DivisionsContactUserSelectItem
    });

    var DivisionsContactItem = Marionette.ItemView.extend({
        template: _.template(DivisionsContactItemTpl),
        tagName: 'tr',
        events: {
            'click button.remove': function(e) {
                e.preventDefault();
                HoneySens.request('accounts:division:contact:remove', this.model);
            },
            'change select[name="type"]': 'changeType',
        },
        onRender: function() {
            var userSelectView = new DivisionsContactUserSelect({el: this.$el.find('select[name="user"]'),
                    collection: HoneySens.request('accounts:division:users')}),
                type = this.model.get('type'),
                view = this;

            userSelectView.render();

            this.$el.find('form').validator().on('submit', function (e) {
                if (!e.isDefaultPrevented()) {
                    e.preventDefault();
                    var newModel = {
                            email: view.$el.find('input[name="email"]').val(),
                            user: view.$el.find('select[name="user"]').val(),
                            sendWeeklySummary: view.$el.find('input[name="weeklySummary"]').is(':checked'),
                            sendCriticalEvents: view.$el.find('input[name="criticalEvents"]').is(':checked'),
                            sendAllEvents: view.$el.find('input[name="allEvents"]').is(':checked'),
                            sendSensorTimeouts: view.$el.find('input[name="sensorTimeouts"]').is(':checked'),
                            type: parseInt(view.$el.find('select[name="type"]').val())
                        };
                    view.model.set(newModel);
                }
            });

            if(type === Models.IncidentContact.type.USER) {
                this.$el.find('select[name="type"]').val(this.model.get('type')).trigger('change');
                userSelectView.$el.val(this.model.get('user'));
            }

            this.refreshTypeSelection(type);
            this.refreshValidators(type);
            this.$el.find('button').tooltip();
        },
        changeType: function() {
            // User manually changed the type
            var type = parseInt(this.$el.find('select[name="type"]').val());
            this.$el.find('input[name="email"]').val('');  // Always clear email field

            this.refreshTypeSelection(type);
            this.refreshValidators(type);
        },
        refreshTypeSelection: function(type) {
            var $form = this.$el.find('form.contactData');

            $form.find('select, input').addClass('hide');
            switch(type) {
                case 0:
                    $form.find('input[name="email"]').removeClass('hide');
                    break;
                case 1:
                    $form.find('select[name="user"]').removeClass('hide');
                    break;
            }
        },
        refreshValidators: function(type) {
            var $form = this.$el.find('form.contactData');

            // reset form
            $form.validator('destroy');
            $form.find('input[name="email"]').attr('data-validate', false).attr('required', false);
            //$form.find('div.form-feedback').addClass('hide');

            switch(type) {
                case 0:
                    $form.find('div.form-feedback').removeClass('hide');
                    this.$el.find('input[name="email"]').attr('data-validate', true).attr('required', true);
                    break;
                case 1:
                    $form.find('div.form-feedback').removeClass('hide');
                    this.$el.find('select[name="user"]').attr('data-validate', true).attr('required', true);
                    break;
            }

            $form.validator('update');
        },
        templateHelpers: {
            getIdentifier: function() {
                // In case this contact has no id yet (i.e. it is new), return a unique id based on time.
                // This is necessary for the panel group to work.
                if(this.id === undefined) {
                    if(this.shadowId === undefined) this.shadowId = new Date().getTime();
                    return this.shadowId;
                } else return this.id;
            }
        }
    });

    Views.DivisionsContactListView = Marionette.CompositeView.extend({
        template: _.template(DivisionsContactListViewTpl),
        childViewContainer: 'tbody',
        childView: DivisionsContactItem,
        events: {
            'click button.add': function(e) {
                e.preventDefault();
                this.collection.add(new Models.IncidentContact());
            }
        },
        initialize: function() {
            var view = this;
            HoneySens.reqres.setHandler('accounts:division:contact:remove', function(contact) {
                view.collection.remove(contact);
            });
        }
    });
});

export default HoneySens.Accounts.Views.DivisionsContactListView;