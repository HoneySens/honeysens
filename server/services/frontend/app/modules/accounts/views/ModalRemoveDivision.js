define(['app/app',
        'app/modules/accounts/templates/ModalRemoveDivision.tpl'],
function(HoneySens, ModalRemoveDivisionTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalRemoveDivision = Marionette.ItemView.extend({
            template: _.template(ModalRemoveDivisionTpl),
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    let archive = this.$el.find('input[name="archive"]').is(':checked');
                    $.ajax({
                        type: 'DELETE',
                        url: 'api/divisions/' + this.model.id,
                        data: JSON.stringify({archive: archive}),
                        success: function() {
                            HoneySens.data.models.divisions.fetch();
                            HoneySens.request('view:modal').empty();
                        }
                    });
                }
            },
            templateHelpers: {
                archivePrefer: function() {
                    return HoneySens.data.settings.get('archivePrefer');
                }
            }
        });
    });

    return HoneySens.Accounts.Views.ModalRemoveDivision;
});