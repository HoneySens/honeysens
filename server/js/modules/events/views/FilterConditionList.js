define(['app/app',
        'app/models',
        'tpl!app/modules/events/templates/FilterConditionListItem.tpl',
        'tpl!app/modules/events/templates/FilterConditionList.tpl'],
function(HoneySens, Models, FilterConditionListItemTpl, FilterConditionListTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        var FilterConditionListItem = Marionette.ItemView.extend({
            template: FilterConditionListItemTpl,
            tagName: 'tr',
            events: {
                'click button.remove': function(e) {
                    e.preventDefault();
                    HoneySens.request('events:filters:condition:remove', this.model);
                },
                'change select[name="attribute"]': 'changeAttribute',
                'change select[name="type"]': 'changeType'
            },
            onRender: function() {
                var view = this,
                    field = this.model.get('field'),
                    type = this.model.get('type'),
                    value = this.model.get('value');

                this.$el.find('form').validator().on('submit', function (e) {  
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();

                        var attribute = parseInt(view.$el.find('select[name="attribute"]').val()),
                            type = parseInt(view.$el.find('select[name="type"]').val()),
                            value = '';
                        switch(attribute) {
                            case Models.EventFilterCondition.field.SOURCE:
                                switch(type) {
                                    case Models.EventFilterCondition.type.SOURCE_STATIC:
                                        value = view.$el.find('input[name="ip_value"]').val();
                                        break;
                                    case Models.EventFilterCondition.type.SOURCE_IPRANGE:
                                        value = view.$el.find('input[name="ip_range_value"]').val();
                                        break;
                                    }
                                break;
                            case Models.EventFilterCondition.field.TARGET:
                                value = view.$el.find('input[name="port_value"]').val();
                                break;
                            case Models.EventFilterCondition.field.CLASSIFICATION:
                                value = view.$el.find('select[name="classification"]').val();
                                break;
                            case Models.EventFilterCondition.field.PROTOCOL:
                                value = view.$el.find('select[name="protocol"]').val();
                                break;
                        }

                        view.model.set({field: attribute, type: type, value: value});
                    }
                });

                this.refreshTypeSelection(field, type);
                this.refreshValueSelection(field, type, value);
                this.refreshValidators(field, type);
                this.$el.find('button').tooltip(); 
            },
            changeAttribute: function() {
                var attribute = parseInt(this.$el.find('select[name="attribute"]').val()),
                    $type = this.$el.find('select[name="type"]');
                this.refreshTypeSelection(attribute);
                this.refreshValueSelection(attribute, parseInt($type.val()));
                this.refreshValidators(attribute, parseInt($type.val()));
            },
            changeType: function() {
                var attribute = parseInt(this.$el.find('select[name="attribute"]').val()),
                    type = parseInt(this.$el.find('select[name="type"]').val());
                this.refreshValueSelection(attribute, type);
                this.refreshValidators(attribute, type);
            },
            /**
             * Render the type selection based on the given attribute value.
             * If preselected is given, the element with the provided id will be the selected one (default is 0).
             */
            refreshTypeSelection: function(attribute, preselected) {
                preselected = preselected || 0;
                var $select = this.$el.find('select[name="type"]');
                $select.empty();
                switch(attribute) {
                    case Models.EventFilterCondition.field.CLASSIFICATION:
                        $select.attr('disabled', 'disabled').append('<option value="0">Klasse</option>');
                        break;
                    case Models.EventFilterCondition.field.SOURCE:
                        $select.attr('disabled', false);
                        $select.append('<option value="' + Models.EventFilterCondition.type.SOURCE_STATIC + '">IP-Adresse</option>');
                        $select.append('<option value="' + Models.EventFilterCondition.type.SOURCE_IPRANGE + '">IP-Bereich</option>');
                        break; 
                    case Models.EventFilterCondition.field.TARGET:
                        $select.attr('disabled', 'disabled').append('<option value="' + Models.EventFilterCondition.type.TARGET_PORT + '">Port</option>');
                        break;
                    case Models.EventFilterCondition.field.PROTOCOL:
                        $select.attr('disabled', 'disabled').append('<option value="0">IPv4</option>');
                        break;
                }
                $select.find('option[value="' + preselected + '"]').prop('selected', true);
            },
            /**
             * Render the value input controls based on the given attribute and type values.
             * If a value is given, it will be treated as default value.
             */    
            refreshValueSelection: function(attribute, type, value) {
                value = value || null;
                var $form = this.$el.find('form.conditionData');
                // hide everything first, then selectively enable whatever is required
                $form.find('select, input').addClass('hide');
                
                switch(attribute) {
                    case Models.EventFilterCondition.field.CLASSIFICATION:
                        $form.find('select[name="classification"]').removeClass('hide')
                            .find('option[value="' + value + '"]').prop('selected', true);
                        break;
                    case Models.EventFilterCondition.field.SOURCE:
                        switch(type) {
                            case Models.EventFilterCondition.type.SOURCE_STATIC:
                                this.$el.find('input[name="ip_value"]').removeClass('hide').val(value);
                                break;
                            case Models.EventFilterCondition.type.SOURCE_IPRANGE:
                                this.$el.find('input[name="ip_range_value"]').removeClass('hide').val(value);
                                break;
                            }
                        break;
                    case Models.EventFilterCondition.field.TARGET:
                        $form.find('input[name="port_value"]').removeClass('hide').val(value);
                        break;
                    case Models.EventFilterCondition.field.PROTOCOL:
                        $form.find('select[name="protocol"]').removeClass('hide')
                            .find('option[value="' + value + '"]').prop('selected', true);
                        break;
                }
            },
            /**
             * Refresh the active validators for the condition data form based on the currently selected attribute and type
             */
            refreshValidators: function(attribute, type) {
                var $form = this.$el.find('form.conditionData');
                // reset form
                $form.validator('destroy');
                $form.find('input[name="port_value"]').attr('data-validate', false).attr('required', false);
                $form.find('input[name="ip_value"]').attr('data-validate', false).attr('required', false);
                $form.find('input[name="ip_range_value"]').attr('data-validate', false).attr('required', false);
                $form.find('div.form-feedback').addClass('hide');
                
                switch(attribute) {
                    case Models.EventFilterCondition.field.CLASSIFICATION:
                        break;
                    case Models.EventFilterCondition.field.SOURCE:
                        $form.find('div.form-feedback').removeClass('hide');
                        switch(type) {
                            case Models.EventFilterCondition.type.SOURCE_STATIC:
                                this.$el.find('input[name="ip_value"]').attr('data-validate', true).attr('required', true);
                                break;
                            case Models.EventFilterCondition.type.SOURCE_IPRANGE:
                                this.$el.find('input[name="ip_range_value"]').attr('data-validate', true).attr('required', true);
                                break;
                        }
                        break;
                    case Models.EventFilterCondition.field.TARGET:
                        $form.find('div.form-feedback').removeClass('hide');
                        this.$el.find('input[name="port_value"]').attr('data-validate', true).attr('required', true);
                        break;
                    case Models.EventFilterCondition.field.PROTOCOL:
                        break;
                }
                $form.validator('update');
            }
        });

        Views.FilterConditionList = Marionette.CompositeView.extend({
            template: FilterConditionListTpl,
            childViewContainer: 'tbody',
            childView: FilterConditionListItem,
            events: {
                'click button.add': function(e) {
                    e.preventDefault();
                    this.collection.add(new Models.EventFilterCondition());
                }
            },
            initialize: function() {
                var view = this;
                HoneySens.reqres.setHandler('events:filters:condition:remove', function(condition) {
                    view.collection.remove(condition);
                });
            }
        });
    });

    return HoneySens.Events.Views.FilterConditionList;
})