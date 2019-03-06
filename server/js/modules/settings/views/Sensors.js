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
                this.$el.find('[data-toggle="popover"]').popover();
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
                        },
                        serviceNetwork: {
                            validators: {
                                notEmpty: {},
                                regexp: {
                                    regexp: /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/,
                                    message: 'Netzbereich bitte als IP-Adresse mit Netzmaske (z.B. 192.168.1.0/24) spezifizieren'
                                }
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var updateInterval = view.$el.find('input[name="updateInterval"]').val(),
                        serviceNetwork = view.$el.find('input[name="serviceNetwork"]').val();
                    view.model.save({sensorsUpdateInterval: updateInterval, sensorsServiceNetwork: serviceNetwork}, {
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