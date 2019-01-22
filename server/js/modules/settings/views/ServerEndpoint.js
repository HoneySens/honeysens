define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'tpl!app/modules/settings/templates/ServerEndpoint.tpl',
        'validate'],
function(HoneySens, ModalSettingsSaveView, ServerEndpointTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ServerEndpoint = Marionette.ItemView.extend({
            template: ServerEndpointTpl,
            className: 'panel-body',
            onRender: function() {
                var view = this;
                // set up validation
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        serverHost: {
                            validators: {
                                notEmpty: {}
                            }
                        },
                        serverPortHTTPS: {
                            validators: {
                                notEmpty: {},
                                between: {
                                    min: 0,
                                    max: 65535
                                }
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var serverHost = view.$el.find('input[name="serverHost"]').val(),
                        serverPortHTTPS = view.$el.find('input[name="serverPortHTTPS"]').val();
                    view.model.save({serverHost: serverHost, serverPortHTTPS: serverPortHTTPS}, {
                        success: function() {
                            HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                        }
                    });
                });
            }
        });
    });

    return HoneySens.Settings.Views.ServerEndpoint;
});