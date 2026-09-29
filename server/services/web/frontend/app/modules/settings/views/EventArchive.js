import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import EventArchiveTpl from 'app/modules/settings/templates/EventArchive.tpl';

const EventArchive = View.extend({
    template: _.template(EventArchiveTpl),
    templateContext: {...i18n},
    className: 'panel-body',
    onRender: function() {
        var view = this;
        this.$el.find('[data-toggle="popover"]').popover();
        this.$el.find('form').validator().on('submit', function(e) {
            if(!e.isDefaultPrevented()) {
                e.preventDefault();

                view.model.save({
                    archivePrefer: view.$el.find('input[name="archivePrefer"]').is(':checked'),
                    archiveMoveDays: parseInt(view.$el.find('input[name="archiveMoveDays"]').val()),
                    archiveKeepDays: parseInt(view.$el.find('input[name="archiveKeepDays"]').val())
                }, {
                    success: function() {
                        radio.request('view:modal').show(new ModalSettingsSaveView());
                    }
                });
            }
        });

    }
});

export default EventArchive;
