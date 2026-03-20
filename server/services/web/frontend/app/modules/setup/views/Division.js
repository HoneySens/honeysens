import HoneySens from 'app/app';
import DivisionTpl from 'app/modules/setup/templates/Division.tpl';

HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Division = Marionette.ItemView.extend({
        template: _.template(DivisionTpl),
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
                    HoneySens.request('setup:install:show', {step: 4, model: view.model});
                }
            });
        }
    });
});

export default HoneySens.Setup.Views.Division;