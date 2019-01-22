define(['app/app', 'app/models',
        'tpl!app/modules/accounts/templates/DivisionsContactItem.tpl',
    ],
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
                'change select[name="type"]': function() {
                    var emailValidator = {
                            validators: {
                                notEmpty: {},
                                emailAddress: {}
                            }
                        },
                        userValidator = {
                            validators: {
                                notEmpty: {}
                            }
                        };
                    this.$el.find('form.contactData').data('bootstrapValidator').resetForm();
                    switch(parseInt(this.$el.find('select[name="type"]').val())) {
                        case 0:
                            this.$el.find('input[name="email"]').removeClass('hide');
                            this.$el.find('select[name="user"]').addClass('hide');
                            this.$el.find('form.contactData').bootstrapValidator('removeField', this.$el.find('select[name="user"]'), userValidator);
                            this.$el.find('form.contactData').bootstrapValidator('addField', this.$el.find('input[name="email"]'), emailValidator);
                            break;
                        case 1:
                            this.$el.find('input[name="email"]').addClass('hide');
                            this.$el.find('select[name="user"]').removeClass('hide');
                            this.$el.find('form.contactData').bootstrapValidator('addField', this.$el.find('select[name="user"]'), userValidator);
                            this.$el.find('form.contactData').bootstrapValidator('removeField', this.$el.find('input[name="email"]'), emailValidator);
                            break;
                    }
                }
            },
            onRender: function() {
                var userSelectView = new DivisionsContactUserSelect({el: this.$el.find('select[name="user"]'),
                    collection: HoneySens.request('accounts:division:users')}),
                    modelType = this.model.getType(),
                    view = this;
                userSelectView.render();
                this.$el.find('form.contactData').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        email: {
                            validators: {
                                notEmpty: {},
                                emailAddress: {}
                            }
                        }
                    }
                }).on('success.form.bv', function() {
                    var type = parseInt(view.$el.find('select[name="type"]').val()),
                        email = type == 0 ? view.$el.find('input[name="email"]').val() : null,
                        user = type == 1 ? view.$el.find('select[name="user"]').val() : null,
                        weeklySummary = view.$el.find('input[name="weeklySummary"]').is(':checked'),
                        criticalEvents = view.$el.find('input[name="criticalEvents"]').is(':checked'),
                        allEvents = view.$el.find('input[name="allEvents"]').is(':checked');
                    view.model.set({email: email, user: user, sendWeeklySummary: weeklySummary,
                        sendCriticalEvents: criticalEvents, sendAllEvents: allEvents});
                });
                if(modelType == Models.IncidentContact.type.USER) {
                    this.$el.find('select[name="type"]').val(this.model.getType()).trigger('change');
                    userSelectView.$el.val(view.model.get('user'));
                }
                this.$el.find('button').tooltip();
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsContactItem;
});