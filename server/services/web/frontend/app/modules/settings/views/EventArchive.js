define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'app/modules/settings/templates/EventArchive.tpl'],
function(HoneySens, ModalSettingsSaveView, EventArchiveTpl) {
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

    return HoneySens.Settings.Views.EventArchive;
});
