define(['app/app', 'app/models',
        'app/modules/accounts/views/DivisionsUserListView',
        'app/modules/accounts/views/DivisionsContactList',
        'tpl!app/modules/accounts/templates/DivisionsEditView.tpl',
        'app/views/common', 'validate'],
function(HoneySens, Models, DivisionsUserListView, DivisionsContactListView, DivisionsEditViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.DivisionsEditView = HoneySens.Views.SlideLayoutView.extend({
            template: DivisionsEditViewTpl,
            className: 'transitionView row',
            regions: {
                users: 'div.userList',
                contacts: 'div.contactList'
            },
            events: {
                'click button.cancel': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:show', {animation: 'slideRight'});
                },
                'click button.save': function(e) {
                    e.preventDefault();
                    var valid = true;
                    this.$el.find('form').bootstrapValidator('validate');
                    this.$el.find('form').each(function() {
                        valid = $(this).data('bootstrapValidator').isValid() && valid;
                    });
                    if(valid) {
                        this.$el.find('button').prop('disabled', true);
                        var model = this.model,
                            name = this.$el.find('input[name="divisionname"]').val(),
                            users = this.getRegion('users').currentView.collection.pluck("id"),
                            contacts = this.getRegion('contacts').currentView.collection;
                        if(!model.id) HoneySens.data.models.divisions.add(model);
                        model.save({name: name, users: users, contacts: contacts.toJSON()}, {success: function() {
                            HoneySens.execute('fetchUpdates');
                            HoneySens.request('accounts:show', {animation: 'slideRight'});
                        }});
                    }
                }
            },
            initialize: function() {
                var view = this;
                this.contactCollection = new Models.IncidentContacts();
                this.userCollection = this.model.getUserCollection();
                if(this.model.id) {
                    this.contactCollection.reset(HoneySens.data.models.contacts.where({division: this.model.id}));
                }
                HoneySens.reqres.setHandler('accounts:division:users', function() {
                    return view.userCollection;
                });
            },
            onRender: function() {
                var view = this;
                this.getRegion('users').show(new DivisionsUserListView({collection: view.userCollection}));
                this.getRegion('contacts').show(new DivisionsContactListView({collection: view.contactCollection}));
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        divisionname: {
                            validators: {
                                notEmpty: {},
                                regexp: {
                                    regexp: /^[a-zA-Z0-9 ]+$/,
                                    message: 'Nur Groß-, Kleinbuchstaben und Zahlen erlaubt'
                                },
                                stringLength: {
                                    min: 1,
                                    max: 255,
                                    message: 'Name muss zwischen 1 und 255 Zeichen lang sein'
                                }
                            }
                        }
                    }
                });
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsEditView;
});