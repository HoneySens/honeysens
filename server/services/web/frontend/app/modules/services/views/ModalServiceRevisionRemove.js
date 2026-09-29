import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalServiceRevisionRemoveTpl from 'app/modules/services/templates/ModalServiceRevisionRemove.tpl';

const ModalServiceRevisionRemove = View.extend({
    template: _.template(ModalServiceRevisionRemoveTpl),
    templateContext: {...i18n},
    events: {
        'click button.btn-primary': function(e) {
            e.preventDefault();
            this.model.destroy({
                wait: true,
                success: function() {
                    radio.request('view:modal').empty();
                },
                error: function() {
                    radio.request('view:modal').empty();
                }
            });
        }
    }
});

export default ModalServiceRevisionRemove;
