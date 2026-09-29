import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import DivisionTpl from 'app/modules/setup/templates/Division.tpl';

const Division = View.extend({
    template: _.template(DivisionTpl),
    templateContext: {...i18n},
    events: {
        'click button:submit': function(e) {
            e.preventDefault();
            this.$el.find('form').trigger('submit');
        }
    },
    onRender: function() {
        var view = this;

        this.$el.find('form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                view.$el.find('button').prop('disabled', true).text('...');
                view.$el.find('input').prop('disabled', true);

                var divisionName = view.$el.find('input[name="divisionName"]').val();
                view.model.set({divisionName: divisionName});
                radio.request('setup:install:show', {step: 4, model: view.model});
            }
        });
    }
});

export default Division;
