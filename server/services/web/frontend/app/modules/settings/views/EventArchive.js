import HoneySens from 'app/app';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import EventArchiveTpl from 'app/modules/settings/templates/EventArchive.tpl';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.EventArchive = Marionette.ItemView.extend({
        template: _.template(EventArchiveTpl),
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
                            HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                        }
                    });
                }
            });

        }
    });
});

export default HoneySens.Settings.Views.EventArchive;