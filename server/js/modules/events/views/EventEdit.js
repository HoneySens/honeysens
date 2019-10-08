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
                        status = this.$el.find('select[name="statusCode"]').val(),
                        comment = this.$el.find('textarea[name="comment"]').val();
                    if(this.collection.length === 1) {
                        // Single model update
                        this.model.save({status: status, comment: comment}, {wait:true,
                            success: function() {
                                HoneySens.request('view:content').overlay.empty();
                            }
                        });
                    } else {
                        // Batch update
                        if (parseInt(status) >= 0) data.status = status;
                        if (comment.length > 0) data.comment = comment;
                        $.ajax({
                            type: 'PUT',
                            url: 'api/events',
                            data: JSON.stringify(data),
                            success: function () {
                                HoneySens.data.models.events.fetch();
                                HoneySens.request('view:content').overlay.empty();
                            }
                        });
                    }
                }
            },
            initialize: function() {
                // Set a model in case we were given a list with just one item
                if(this.collection.length === 1) this.model = this.collection.first();
            },
            onRender: function() {
                if(this.collection.length === 1) {
                    this.$el.find('select[name="statusCode"] option[value="' + this.model.get('status') + '"]').prop('selected', true);
                    this.$el.find('textarea').val(this.model.get('comment'));
                }
            },
            templateHelpers: {
                getEventCount: function() {
                    return typeof this.items === 'undefined' ? 1 : this.items.length;
                }
            }
        });
    });

    return HoneySens.Events.Views.EventEdit;
});