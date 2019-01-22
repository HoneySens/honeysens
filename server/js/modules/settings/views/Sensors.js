define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'tpl!app/modules/settings/templates/Sensors.tpl',
        'validate'],
function(HoneySens, ModalSettingsSaveView, SensorsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Sensors = Marionette.ItemView.extend({
            template: SensorsTpl,
            className: 'panel-body',
            onRender: function() {
                var view = this;
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        updateInterval: {
                            validators: {
                                notEmpty: {},
                                between: {
                                    min: 1,
                                    max: 60,
                                    message: 'Das Intervall muss minimal 1 und maximal 60 Minuten betragen'
                                }
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var updateInterval = view.$el.find('input[name="updateInterval"]').val();
                    view.model.save({sensorsUpdateInterval: updateInterval}, {
                        success: function() {
                            HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                        }
                    });
                });
            }
        });
    });

    return HoneySens.Settings.Views.Sensors;
});