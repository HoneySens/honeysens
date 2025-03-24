import HoneySens from 'app/app';
import Models from 'app/models';
import EventEditTpl from 'app/modules/events/templates/EventEdit.tpl';
import 'app/views/common';
import 'validator';

HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.EventEdit = Marionette.ItemView.extend({
        template: _.template(EventEditTpl),
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
                    model = this.model,
                    valid = true;
                // Perform validation
                this.$el.find('form').validator('validate');
                this.$el.find('.form-group').each(function() {
                    valid = !$(this).hasClass('has-error') && valid;
                });
                if(valid) {
                    let isSingleEdit = !this.model.has('total');

                    // Set comment and status depending on whether a single or multiple events are edited
                    if(isSingleEdit) {
                        // For single edits we can simply diff the current state with the model to spot changes
                        if(parseInt(status) !== model.get('status')) data.new_status = status;
                        if(comment !== model.get('comment')) data.new_comment = comment;
                    } else {
                        // When editing multiple events, users have to explicitly select or enter new values
                        if(parseInt(status) >= 0) data.new_status = status;
                        // If the comment field is left empty, existing comments will be preserved
                        if(comment.length > 0) data.new_comment = comment;
                    }
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
            },
            showLastModificationTime: function() {
                if(this.lastModificationTime) return HoneySens.Views.EventTemplateHelpers.showTimestamp(this.lastModificationTime);
                else return '-'
            }
        },
        refreshValidators: function(statusCode) {
            var requireComment = HoneySens.data.settings.get('requireEventComment');
            this.$el.find('textarea[name="comment"]').prop('required', requireComment && parseInt(statusCode) > Models.Event.status.UNEDITED);
            this.$el.find('form').validator('validate');
        }
    });
});

export default HoneySens.Events.Views.EventEdit;