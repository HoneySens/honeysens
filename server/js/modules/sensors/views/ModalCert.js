define(['app/app',
        'tpl!app/modules/sensors/templates/ModalCert.tpl'],
function(HoneySens, ModalCertTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalCert = Marionette.ItemView.extend({
            template: ModalCertTpl,
            modelEvents: {
                'change': 'render'
            },
            initialize: function() {
                this.model.set('fingerprint', this.model.get('fingerprint') || 'Lädt...');
                this.model.fetch();
            }
        });
    });

    return HoneySens.Sensors.Views.ModalCert;
});