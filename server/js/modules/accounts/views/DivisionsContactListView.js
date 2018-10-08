define(['app/app',
        'app/models',
        'tpl!app/modules/accounts/templates/DivisionsContactItem.tpl',
        'tpl!app/modules/accounts/templates/DivisionsContactListView.tpl'],
function(HoneySens, Models, DivisionsContactItemTpl, DivisionsContactListViewTpl) {
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
            template: DivisionsContactItemTpl,
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
                    view = this;
                var type = parseInt(this.$el.find('select[name="type"]').val());

                userSelectView.render();

                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();

                        type = parseInt(view.$el.find('select[name="type"]').val());
                        var email = type == 0 ? view.$el.find('input[name="email"]').val() : null,
                            user = type == 1 ? view.$el.find('select[name="user"]').val() : null,
                            weeklySummary = view.$el.find('input[name="weeklySummary"]').is(':checked'),
                            criticalEvents = view.$el.find('input[name="criticalEvents"]').is(':checked'),
                            allEvents = view.$el.find('input[name="allEvents"]').is(':checked');

                        view.model.set({email: email, user: user, sendWeeklySummary: weeklySummary,
                            sendCriticalEvents: criticalEvents, sendAllEvents: allEvents});
                    }
                });

                if(type == Models.IncidentContact.type.USER) {
                    this.$el.find('select[name="type"]').val(this.model.getType()).trigger('change');
                    userSelectView.$el.val(view.model.get('user'));
                }

                this.refreshTypeSelection(type);
                this.refreshValidators(type);
                this.$el.find('button').tooltip();
            },
            changeType: function() {
                var type = parseInt(this.$el.find('select[name="type"]').val());

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
            }
        });

        Views.DivisionsContactListView = Marionette.CompositeView.extend({
            template: DivisionsContactListViewTpl,
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

    return HoneySens.Accounts.Views.DivisionsContactListView;
});

