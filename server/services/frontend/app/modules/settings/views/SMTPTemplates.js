define(['app/app',
        'app/modules/settings/views/ModalSettingsSave',
        'app/modules/settings/views/ModalSMTPTemplatePreview',
        'app/modules/settings/templates/SMTPTemplates.tpl',
        'app/modules/settings/templates/SMTPTemplateDetails.tpl'],
function(HoneySens, ModalSettingsSaveView, ModalSMTPTemplatePreviewView, SMTPTemplatesTpl, SMTPTemplateDetailsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        // Inline views to render the template selector
        var TemplateDropdownItem = Marionette.ItemView.extend({
            template: _.template('<%- name %>'),
            tagName: 'option',
            onRender: function() {
                this.$el.attr('value', this.model.id);
            }
        });

        var TemplateDropdownView = Marionette.CollectionView.extend({
            template: false,
            childView: TemplateDropdownItem,
            events: {
                'change': function() {
                    HoneySens.request('settings:templates:show', this.$el.val());
                }
            }
        });

        var TemplateDetailsView = Marionette.ItemView.extend({
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
                    HoneySens.request('view:modal').show(new ModalSMTPTemplatePreviewView({model: new Backbone.Model({preview: preview})}));
                },
                'submit': function(e) {
                    e.preventDefault();

                    let hasOverlay = this.$el.find('input[name="hasOverlay"]').is(':checked'),
                        template = hasOverlay ? this.$el.find('textarea[name="templateContent"]').val() : null;
                    if(hasOverlay || this.model.get('overlay') !== null) {
                        this.model.save({template: template}, {
                            success: function() {
                                HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                            }
                        });
                    }

                }
            },
            modelEvents: {
                change: 'render'
            },
            onRender: function() {
                let activeTemplate = this.model.get('overlay') !== null ? this.model.get('overlay').template : this.model.get('template');
                this.$el.find('textarea[name="templateContent"]').val(activeTemplate);
            },
            templateHelpers: {
                hasOverlay: function() {
                    return this.overlay !== null;
                }
            }
        });

        Views.SMTPTemplates = Marionette.LayoutView.extend({
            template: _.template(SMTPTemplatesTpl),
            className: 'panel-body',
            regions: {
                templateDetails: '#templateDetails'
            },
            initialize: function() {
                var view = this;
                HoneySens.reqres.setHandler('settings:templates:show', function (type) {
                    view.getRegion('templateDetails').show(new TemplateDetailsView({model: view.collection.get(type)}));
                });
                this.listenTo(this.collection, 'update', function(c) {
                    HoneySens.request('settings:templates:show', c.at(0).id);
                });
            },
            onRender: function() {
                // Attach selector view to existing element
                var templateSelector = new TemplateDropdownView({el: this.$el.find('select[name=templateType]'), collection: this.collection});
                templateSelector.render();
            },
            onDestroy: function() {
                HoneySens.reqres.removeHandler('settings:templates:show');
            }
        });
    });

    return HoneySens.Settings.Views.SMTPTemplates;
});