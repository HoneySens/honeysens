define(['app/app',
        'app/models',
        'app/modules/events/views/FilterConditionList',
        'tpl!app/modules/events/templates/FilterEdit.tpl',
        'validator'],
function(HoneySens, Models, FilterConditionListView, FilterEditTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.FilterEdit = Marionette.LayoutView.extend({
            template: FilterEditTpl,
            className: 'container-fluid',
            regions: {
                conditions: 'div.conditionList'
            },
            events: {
                'click button.cancel': function(e) {
                    e.preventDefault();
                    HoneySens.request('view:content').overlay.empty();
                },
                'click button.save': function(e) {
                    e.preventDefault();
                    var valid = true;

                    this.$el.find('form').validator('validate');
                    this.$el.find('.form-group').each(function() {
                        valid = !$(this).hasClass('has-error') && valid;
                    });

                    if(valid) {
                        this.$el.find('form').trigger('submit');
                        this.$el.find('button').prop('disabled', true);

                        var model = this.model,
                            name = this.$el.find('input[name="filtername"]').val(),
                            type = parseInt(this.$el.find('select[name="type"]').val()),
                            division = parseInt(this.$el.find('select[name="division"]').val()),
                            conditions = this.conditionCollection.toJSON();
                        if(!model.id) HoneySens.data.models.eventfilters.add(model);
                        model.save({name: name, type: type, division: division, conditions: conditions}, {success: function() {
                            HoneySens.request('view:content').overlay.empty();
                        }});
                    }
                }
            },
            initialize: function() {
                this.conditionCollection = this.model.getConditionCollection();
            },
            onRender: function() {
                var view = this;

                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();
                    }
                });

                this.getRegion('conditions').show(new FilterConditionListView({collection: view.conditionCollection}));
            },
            templateHelpers: {
                isNew: function() {
                    return !this.hasOwnProperty('id');
                }
            },
            serializeData: function() {
                var data = Marionette.ItemView.prototype.serializeData.apply(this, arguments);
                data.divisions = HoneySens.data.models.divisions.toJSON();
                return data;
            }
        });
    });

    return HoneySens.Events.Views.FilterEdit;
});