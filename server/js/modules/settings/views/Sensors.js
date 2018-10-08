define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'tpl!app/modules/settings/templates/Sensors.tpl',
        'validator'],
function(HoneySens, ModalSettingsSaveView, SensorsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Sensors = Marionette.ItemView.extend({
            template: SensorsTpl,
            className: 'panel-body',
            onRender: function() {
                var view = this;
                this.$el.find('[data-toggle="popover"]').popover();
                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();

                        var updateInterval = view.$el.find('input[name="updateInterval"]').val();
                        view.model.save({sensorsUpdateInterval: updateInterval}, {
                            success: function() {
                                HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                            }
                        });
                    }
                });
            }
        });
    });

    return HoneySens.Settings.Views.Sensors;
});