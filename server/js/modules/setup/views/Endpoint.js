define(['app/app',
        'tpl!app/modules/setup/templates/Endpoint.tpl'],
function(HoneySens, EndpointTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Endpoint = Marionette.ItemView.extend({
            template: EndpointTpl,
            events: {
                'click button:submit': function(e) {
                    e.preventDefault();
                    this.$el.find('form').bootstrapValidator('validate');
                }
            },
            onRender: function() {
                var view = this;
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        serverEndpoint: {
                            validators: {
                                notEmpty: {}
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var serverEndpoint = view.$el.find('input[name="serverEndpoint"]').val();
                    view.model.set({serverEndpoint: serverEndpoint});
                    HoneySens.request('setup:install:show', {step: 3, model: view.model});
                });
            },
            templateHelpers: {
                showCertCN: function() {
                    return HoneySens.data.system.get('cert_cn');
                }
            }
        });

    });

    return HoneySens.Setup.Views.Endpoint;
});