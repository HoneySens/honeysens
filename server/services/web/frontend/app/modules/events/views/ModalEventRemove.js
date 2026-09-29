import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import HoneySens from 'app/app';
import ModalEventRemoveSingleTpl from 'app/modules/events/templates/ModalEventRemoveSingle.tpl';
import ModalEventRemoveMassTpl from 'app/modules/events/templates/ModalEventRemoveMass.tpl';
import { EventTemplateHelpers } from 'app/views/common';

const ModalRemoveEvent = View.extend({
    events: {
        'click button.btn-primary': function(e) {
            e.preventDefault();
            this.trigger('confirm', this.$el.find('input[name="archive"]').is(':checked'));
        }
    },
    initialize: function() {
        // Template selection based on single/mass event removal: in case of multiple events just their 'total'
        // count is submitted, otherwise we receive an Event object
        if(this.model.has('total')) this.template = _.template(ModalEventRemoveMassTpl);
        else this.template = _.template(ModalEventRemoveSingleTpl);
    },
    templateContext: _.extend({
        archivePrefer: function() {
            return HoneySens.data.settings.get('archivePrefer');
        },
        t: i18n.t
    }, EventTemplateHelpers)
});

export default ModalRemoveEvent;
