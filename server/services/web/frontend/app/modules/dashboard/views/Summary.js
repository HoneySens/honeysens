import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import SummaryTpl from 'app/modules/dashboard/templates/Summary.tpl';

const Summary = View.extend({
    template: _.template(SummaryTpl),
    templateContext: {...i18n},
    className: 'panel panel-primary',
    onModelSync: function() {
        this.model.recalculate();
        this.render();
    },
    modelEvents: {
        sync: 'onModelSync'
    }
});

export default Summary;
