define(['app/app',
        'tpl!app/modules/events/templates/EventEdit.tpl',
        'app/views/common'],
function(HoneySens, EventEditTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.EventEdit = Marionette.ItemView.extend({
            template: EventEditTpl,
            className: 'container-fluid',
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                },
                'click button:submit': function(e) {
                    e.preventDefault();
                    var data = {ids: this.collection.pluck('id')},
                        status = this.$el.find('select[name="statusCode"]').val();
                    if(parseInt(status) >= 0) data.status = status;
                    else {
                        // Don't do anything if no status was selected
                        HoneySens.request('view:content').overlay.empty();
                        return;
                    }
                    $.ajax({
                        type: 'PUT',
                        url: 'api/events',
                        data: JSON.stringify(data),
                        success: function() {
                            HoneySens.data.models.events.fetch();
                            HoneySens.request('view:content').overlay.empty();
                        }
                    });
                }
            }
        });
    });

    return HoneySens.Events.Views.EventEdit;
});