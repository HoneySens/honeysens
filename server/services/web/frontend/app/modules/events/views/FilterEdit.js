import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import FilterConditionListView from 'app/modules/events/views/FilterConditionList';
import FilterEditTpl from 'app/modules/events/templates/FilterEdit.tpl';
import 'validator';

const FilterEdit = View.extend({
    template: _.template(FilterEditTpl),
    className: 'container-fluid',
    regions: {
        conditions: 'div.conditionList'
    },
    events: {
        'click button.cancel': function(e) {
            e.preventDefault();
            radio.request('view:content').getRegion('overlay').empty();
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
                    description = this.$el.find('textarea[name="description"]').val(),
                    division = parseInt(this.$el.find('select[name="division"]').val()),
                    conditions = this.conditionCollection.toJSON();
                if(!model.id) HoneySens.data.models.eventfilters.add(model);
                model.save({name: name, description: description, division: division, conditions: conditions},
                    {success: function() {
                        HoneySens.data.models.eventfilters.fetch();
                        radio.request('view:content').getRegion('overlay').empty();
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
        this.$el.find('textarea[name="description"]').val(this.model.get('description'));
    },
    templateContext: {
        ...i18n,
        isNew: function() {
            return !this.hasOwnProperty('id');
        },
        requireFilterDescription: function() {
            return HoneySens.data.settings.get('requireFilterDescription');
        }
    },
    serializeData: function() {
        var data = View.prototype.serializeData.apply(this, arguments);
        data.divisions = HoneySens.data.models.divisions.toJSON();
        return data;
    }
});

export default FilterEdit;
