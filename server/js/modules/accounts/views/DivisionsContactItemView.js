define(['app/app', 'app/models',
        'tpl!app/modules/accounts/templates/DivisionsContactItem.tpl'],
function(HoneySens, Models, DivisionsContactItemTpl) {
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

        Views.DivisionsContactItem = Marionette.ItemView.extend({
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
                    modelType = this.model.getType(),
                    view = this;
                var type = this.model.getType();
                
                userSelectView.render();

                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();

                        type = parseInt(view.$el.find('select[name="type"]').val()),
                        email = type == 0 ? view.$el.find('input[name="email"]').val() : null,
                        user = type == 1 ? view.$el.find('select[name="user"]').val() : null,
                        weeklySummary = view.$el.find('input[name="weeklySummary"]').is(':checked'),
                        criticalEvents = view.$el.find('input[name="criticalEvents"]').is(':checked'),
                        allEvents = view.$el.find('input[name="allEvents"]').is(':checked');
                        
                        view.model.set({email: email, user: user, sendWeeklySummary: weeklySummary,
                        sendCriticalEvents: criticalEvents, sendAllEvents: allEvents});
                    }
                });

                if(modelType == Models.IncidentContact.type.USER) {
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
                
                $form.validator('destroy');
                this.$el.find('input[name="email"]').attr('data-validate', false).attr('required', false);

                switch(type) {
                    case 0:
                        this.$el.find('input[name="email"]').attr('data-validate', true).attr('required', true);
                        break;
                    case 1: 
                        this.$el.find('select[name="user"]').attr('data-validate', true).attr('required', true);
                        break;
                }

                $form.validator('update');
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsContactItem;
});