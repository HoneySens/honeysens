import HoneySens from 'app/app';
import SummaryTpl from 'app/modules/dashboard/templates/Summary.tpl';

HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Summary = Marionette.ItemView.extend({
        template: _.template(SummaryTpl),
        className: 'panel panel-primary',
        onModelChange: function() {
            this.model.recalculate();
            this.render();
        },
        modelEvents: {
            change: 'onModelChange'
        }
    });
});

export default HoneySens.Dashboard.Views.Summary;