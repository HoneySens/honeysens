import i18n from 'app/common/i18n';
import { View, CollectionView } from 'backbone.marionette';
import Backbone from 'backbone';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import ModalSMTPTemplatePreviewView from 'app/modules/settings/views/ModalSMTPTemplatePreview';
import SMTPTemplatesTpl from 'app/modules/settings/templates/SMTPTemplates.tpl';
import SMTPTemplateDetailsTpl from 'app/modules/settings/templates/SMTPTemplateDetails.tpl';

// Inline views to render the template selector
var TemplateDropdownItem = View.extend({
    template: _.template('<%- name %>'),
    tagName: 'option',
    onRender: function() {
        this.$el.attr('value', this.model.id);
    }
});

var TemplateDropdownView = CollectionView.extend({
    template: false,
    childView: TemplateDropdownItem,
    events: {
        'change': function() {
            radio.request('settings:templates:show', this.$el.val());
        }
    }
});

var TemplateDetailsView = View.extend({
    template: _.template(SMTPTemplateDetailsTpl),
    tagName: 'form',
    events: {
        'change input[name="hasOverlay"]': function(e) {
            let hasOverlay = e.target.checked,
                $content = this.$el.find('textarea[name="templateContent"]');
            // If overlay is disabled, show the default template
            if(!hasOverlay) $content.val(this.model.get('template'));
            $content.prop('disabled', !hasOverlay);
        },
        'click button.preview': function() {
            let hasOverlay = this.$el.find('input[name="hasOverlay"]').is(':checked'),
                preview = hasOverlay ? this.$el.find('textarea[name="templateContent"]').val() : this.model.get('template');
            // Assemble preview by substituting template variables with preview content
            _.each(this.model.get('preview'), function(content, variable) {
                preview = preview.replace('{{' + variable + '}}', content);
            });
            radio.request('view:modal').show(new ModalSMTPTemplatePreviewView({model: new Backbone.Model({preview: preview})}));
        },
        'submit': function(e) {
            e.preventDefault();

            let hasOverlay = this.$el.find('input[name="hasOverlay"]').is(':checked'),
                template = hasOverlay ? this.$el.find('textarea[name="templateContent"]').val() : null;
            if(hasOverlay || this.model.get('overlay') !== null) {
                this.model.save({template: template}, {
                    success: function() {
                        radio.request('view:modal').show(new ModalSettingsSaveView());
                    }
                });
            }

        }
    },
    onRender: function() {
        let activeTemplate = this.model.get('overlay') !== null ? this.model.get('overlay').template : this.model.get('template');
        this.$el.find('textarea[name="templateContent"]').val(activeTemplate);
    },
    templateContext: {
        ...i18n,
        hasOverlay: function() {
            return this.overlay !== null;
        }
    }
});

const SMTPTemplates = View.extend({
    template: _.template(SMTPTemplatesTpl),
    templateContext: {...i18n},
    className: 'panel-body',
    regions: {
        templateDetails: '#templateDetails'
    },
    initialize: function() {
        var view = this;
        radio.reply('settings:templates:show', function (type) {
            view.getRegion('templateDetails').show(new TemplateDetailsView({model: view.collection.get(type)}));
        });
        this.listenTo(this.collection, 'update', function(c) {
            radio.request('settings:templates:show', c.at(0).id);
        });
    },
    onRender: function() {
        // Attach selector view to existing element
        var templateSelector = new TemplateDropdownView({el: this.$el.find('select[name=templateType]'), collection: this.collection});
        templateSelector.render();
    },
    onDestroy: function() {
        radio.stopReplying('settings:templates:show');
    }
});

export default SMTPTemplates;
