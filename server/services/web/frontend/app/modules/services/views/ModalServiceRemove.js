import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalServiceRemoveTpl from 'app/modules/services/templates/ModalServiceRemove.tpl';

const ModalServiceRemove = View.extend({
    template: _.template(ModalServiceRemoveTpl),
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

export default ModalServiceRemove;
