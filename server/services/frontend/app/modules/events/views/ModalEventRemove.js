define(['app/app',
        'app/modules/events/templates/ModalEventRemoveSingle.tpl',
        'app/modules/events/templates/ModalEventRemoveMass.tpl',
        'app/views/common'],
function(HoneySens, ModalEventRemoveSingleTpl, ModalEventRemoveMassTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalRemoveEvent = Marionette.ItemView.extend({
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    this.trigger('confirm', this.$el.find('input[name="archive"]').is(':checked'));
                }
            },
            initialize: function() {
                // Template selection based on single/mass event removal: in case of multiple events just their 'total'
                // count is submitted, otherwise we receive an Event object
                if(this.model.has('total')) this.template = _.template(ModalEventRemoveMassTpl);
                else this.template = _.template(ModalEventRemoveSingleTpl);
            },
            templateHelpers: Object.assign({
                archivePrefer: function() {
                    return HoneySens.data.settings.get('archivePrefer');
                }
            }, HoneySens.Views.EventTemplateHelpers)
        });
    });

    return HoneySens.Events.Views.ModalRemoveEvent;
});
