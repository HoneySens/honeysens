import HoneySens from 'app/app';
import SummaryTpl from 'app/modules/dashboard/templates/Summary.tpl';

HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Summary = Marionette.ItemView.extend({
        template: _.template(SummaryTpl),
        className: 'panel panel-primary',
        onModelSync: function() {
            this.model.recalculate();
            this.render();
        },
        modelEvents: {
            sync: 'onModelSync'
        }
    });
});

export default HoneySens.Dashboard.Views.Summary;