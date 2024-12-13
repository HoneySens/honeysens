define(['app/app',
        'app/models',
        'app/modules/settings/views/ModalSettingsSave',
        'app/modules/settings/views/ModalForwardTestEvent',
        'tpl!app/modules/settings/templates/EventForwarding.tpl',
        'validator'],
function(HoneySens, Models, ModalSettingsSaveView, ModalForwardTestEvent, EventForwardingTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.EventForwarding = Marionette.ItemView.extend({
            template: EventForwardingTpl,
            className: 'panel-body',
            events: {
                'click button.reset': function() {
                    this.$el.find('form').trigger('reset');
                    this.resetDropdownsFromModel();
                },
                'click button.sendTestEvent': function() {
                    this.enableSection();
                    if(this.isFormValid()) {
                        // Send form data to the server, then instruct to send a test event
                        this.model.save(this.getFormData(), {
                            success: function() {
                                $.ajax({
                                    dataType: 'json',
                                    method: 'POST',
                                    url: 'api/settings/testevent',
                                    success: function(result) {
                                        HoneySens.request('view:modal').show(new ModalForwardTestEvent({model: new Models.Event(result)}));
                                    }
                                });
                            }
                        });
                    }
                    this.disableSection();
                }
            },
            onRender: function() {
                var view = this;
                this.resetDropdownsFromModel();
                // Submission handler
                this.$el.find('form').validator().on('submit', function (e) {
                    if(!e.isDefaultPrevented()) {
                        e.preventDefault();
                        view.model.save(view.getFormData(), {
                            success: function() {
                                HoneySens.request('view:modal').show(new ModalSettingsSaveView());
                            }
                        });
                    }
                });
            },
            isFormValid: function() {
                var $form = this.$el.find('form');
                return !$form.validator('validate').has('.has-error').length;
            },
            enableSection: function() {
                this.$el.find('input').attr('required', true);
            },
            disableSection: function() {
                this.$el.find('input').attr('required', false);
            },
            getFormData: function() {
                return {
                    syslogServer: this.$el.find('input[name="syslogServer"]').val(),
                    syslogPort: parseInt(this.$el.find('input[name="syslogPort"]').val()),
                    syslogTransport: parseInt(this.$el.find('select[name="syslogTransport"]').val()),
                    syslogFacility: parseInt(this.$el.find('select[name="syslogFacility"]').val()),
                    syslogPriority: parseInt(this.$el.find('select[name="syslogPriority"]').val()),
                }
            },
            resetDropdownsFromModel: function() {
                this.$el.find('select[name="syslogTransport"] option[value="' + this.model.get('syslogTransport') + '"]').prop('selected', true);
                this.$el.find('select[name="syslogFacility"] option[value="' + this.model.get('syslogFacility') + '"]').prop('selected', true);
                this.$el.find('select[name="syslogPriority"] option[value="' + this.model.get('syslogPriority')+ '"]').prop('selected', true);
            }
        });
    });

    return HoneySens.Settings.Views.EventForwarding;
});
