define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'tpl!app/modules/settings/templates/Logging.tpl',
        'validator'],
function(HoneySens, ModalSettingsSaveView, LoggingTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Logging = Marionette.ItemView.extend({
            template: LoggingTpl,
            className: 'panel-body',
            onRender: function() {
                var view = this;
                this.$el.find('[data-toggle="popover"]').popover();
                this.$el.find('form').validator().on('submit', function(e) {
                    if(!e.isDefaultPrevented()) {
                        e.preventDefault();

                        var keepDays = view.$el.find('input[name="keepDays"]').val();
                        view.model.save({
                            apiLogKeepDays: keepDays}, {
                            success: function() {
                                HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                            }
                        });
                    }
                });
            }
        });
    });

    return HoneySens.Settings.Views.Logging;
});