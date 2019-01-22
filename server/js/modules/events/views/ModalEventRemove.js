define(['app/app',
        'tpl!app/modules/events/templates/ModalEventRemoveSingle.tpl',
        'tpl!app/modules/events/templates/ModalEventRemoveMass.tpl',
        'app/views/common'],
function(HoneySens, ModalEventRemoveSingleTpl, ModalEventRemoveMassTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalRemoveEvent = Marionette.ItemView.extend({
            templateHelpers: HoneySens.Views.EventTemplateHelpers,
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    if(this.hasOwnProperty('model')) {
                        this.model.destroy({
                            wait: true, success: function () {
                                HoneySens.data.models.events.fetch();
                                HoneySens.request('view:modal').empty();
                            }
                        });
                    } else if(this.hasOwnProperty('collection')) {
                        $.ajax({
                            type: 'DELETE',
                            url: 'api/events',
                            data: JSON.stringify(this.collection.pluck('id')),
                            success: function() {
                                HoneySens.data.models.events.fetch();
                                HoneySens.request('view:modal').empty();
                            }
                        });
                    }
                }
            },
            initialize: function() {
                // Template selection based on single/mass event removal
                if(this.hasOwnProperty('model')) this.template = ModalEventRemoveSingleTpl;
                else if(this.hasOwnProperty('collection')) this.template = ModalEventRemoveMassTpl;
            }
        });
    });

    return HoneySens.Events.Views.ModalRemoveEvent;
});
