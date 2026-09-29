import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalFilterRemoveTpl from 'app/modules/events/templates/ModalFilterRemove.tpl';

const ModalFilterRemove = View.extend({
    template: _.template(ModalFilterRemoveTpl),
    templateContext: {...i18n},
    events: {
        'click button.btn-primary': function(e) {
            e.preventDefault();
            this.model.destroy({wait: true, success: function() {
                radio.request('view:modal').empty();
            }});
        }
    }
});

export default ModalFilterRemove;
