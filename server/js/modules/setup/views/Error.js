define(['app/app',
        'tpl!app/modules/setup/templates/Error.tpl'],
function(HoneySens, ErrorTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Error = Marionette.ItemView.extend({
            template: ErrorTpl,
            onRender: function() {
                var errorText;
                switch(this.model.get('code')) {
                    case 1: errorText = 'Server-Konfiguration konnte nicht geschrieben werden.'; break;
                    default: errorText = 'Auf dem Server ist ein Fehler aufgetreten.'; break;
                }
                this.$el.find('p').text(errorText);
            }
        });
    });

    return HoneySens.Setup.Views.Error;
});