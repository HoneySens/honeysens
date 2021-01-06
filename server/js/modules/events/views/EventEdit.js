define(['app/app',
        'app/models',
        'tpl!app/modules/events/templates/EventEdit.tpl',
        'app/views/common',
        'validator'],
function(HoneySens, Models, EventEditTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.EventEdit = Marionette.ItemView.extend({
            template: EventEditTpl,
            className: 'container-fluid',
            events: {
                'change select[name="statusCode"]': function(e) {
                    this.refreshValidators(e.target.value);
                },
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                },
                'click button:submit': function(e) {
                    e.preventDefault();
                    var status = this.$el.find('select[name="statusCode"]').val(),
                        comment = this.$el.find('textarea[name="comment"]').val(),
                        data = {},
                        valid = true;
                    // Perform validation
                    this.$el.find('form').validator('validate');
                    this.$el.find('.form-group').each(function() {
                        valid = !$(this).hasClass('has-error') && valid;
                    });
                    if(valid) {
                        // Set status/comment if editing a single model or if something was entered when editing multiple
                        if (parseInt(status) >= 0 || !this.model.has('total')) data.new_status = status;
                        if (comment.length > 0 || !this.model.has('total')) data.new_comment = comment;
                        this.trigger('confirm', data);
                    }
                }
            },
            onRender: function() {
                if(!this.model.has('total')) {
                    this.$el.find('select[name="statusCode"] option[value="' + this.model.get('status') + '"]').prop('selected', true);
                    this.$el.find('textarea').val(this.model.get('comment'));
                }
                this.refreshValidators(this.model.get('status'));
            },
            templateHelpers: {
                isMultiEdit: function() {
                    return typeof this.total !== 'undefined';
                }
            },
            refreshValidators: function(statusCode) {
                var requireComment = HoneySens.data.settings.get('requireEventComment');
                this.$el.find('textarea[name="comment"]').prop('required', requireComment && parseInt(statusCode) > Models.Event.status.UNEDITED);
                this.$el.find('form').validator('validate');
            }
        });
    });

    return HoneySens.Events.Views.EventEdit;
});