define(['app/app',
        'tpl!app/common/templates/ModalServerError.tpl'],
function(HoneySens, ModalDuplicateNameTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalDuplicateName = Marionette.ItemView.extend({
            template: ModalDuplicateNameTpl,
            templateHelpers: {
                getMessage: function() {
                    var msg = null;
                    try {
                        // In case a 'msg' property is defined, use it.
                        if(this.hasOwnProperty('msg')) msg = this.msg;
                        else {
                            // Otherwise, try to parse the response as JSON and lookup the 'code' attribute
                            var code = JSON.parse(this.xhr.responseText).code;
                            if (this.errors.hasOwnProperty(code)) msg = this.errors[code];
                        }
                    } catch(e) {}
                    return msg !== null ? msg : 'Auf dem Server ist ein Fehler aufgetreten.';
                }
            },
            onDestroy: function() {
                if(this.model.has('onClose')) this.model.attributes.onClose();
            }
        });
    });

    return HoneySens.Accounts.Views.ModalDuplicateName;
});