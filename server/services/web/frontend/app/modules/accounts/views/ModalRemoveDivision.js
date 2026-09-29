import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import ModalRemoveDivisionTpl from 'app/modules/accounts/templates/ModalRemoveDivision.tpl';

const ModalRemoveDivision = View.extend({
    template: _.template(ModalRemoveDivisionTpl),
    events: {
        'click button.btn-primary': function(e) {
            e.preventDefault();
            let archive = this.$el.find('input[name="archive"]').is(':checked');
            $.ajax({
                type: 'DELETE',
                url: 'api/divisions/' + this.model.id,
                data: JSON.stringify({archive: archive}),
                success: function() {
                    HoneySens.data.models.divisions.fetch();
                    radio.request('view:modal').empty();
                }
            });
        }
    },
    templateContext: {
        ...i18n,
        archivePrefer: function() {
            return HoneySens.data.settings.get('archivePrefer');
        }
    }
});

export default ModalRemoveDivision;
