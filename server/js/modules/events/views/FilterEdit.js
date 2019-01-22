define(['app/app',
        'app/models',
        'app/modules/events/views/FilterConditionList',
        'tpl!app/modules/events/templates/FilterEdit.tpl',
        'validate'],
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
                    this.$el.find('form').bootstrapValidator('validate');
                    this.$el.find('form').each(function() {
                        valid = $(this).data('bootstrapValidator').isValid() && valid;
                    });
                    if(valid) {
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
                this.getRegion('conditions').show(new FilterConditionListView({collection: this.conditionCollection}));
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        filtername: {
                            validators: {
                                notEmpty: {},
                                regexp: {
                                    regexp: /^[a-zA-Z0-9._\- ]+$/,
                                    message: 'Erlaubte Zeichen: a-Z, 0-9, _, -, .'
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