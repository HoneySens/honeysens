define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'tpl!app/modules/settings/templates/ServerEndpoint.tpl',
        'validator'],
function(HoneySens, ModalSettingsSaveView, ServerEndpointTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ServerEndpoint = Marionette.ItemView.extend({
            template: ServerEndpointTpl,
            className: 'panel-body',
            onRender: function() {
                var view = this;
                
                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();
                        
                        var serverHost = view.$el.find('input[name="serverHost"]').val();
                        var serverPortHTTPS = view.$el.find('input[name="serverPortHTTPS"]').val();
                        view.model.save({serverHost: serverHost, serverPortHTTPS: serverPortHTTPS}, {
                            success: function() {
                                HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                            }
                        });
                    }
                });
            }
        });
    });

    return HoneySens.Settings.Views.ServerEndpoint;
});