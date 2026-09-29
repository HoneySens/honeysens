import i18n from 'app/common/i18n';
import { ChannelEncryption } from 'app/models';
import { View } from 'backbone.marionette';
import Backbone from 'backbone';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import ModalSendTestMail from 'app/modules/settings/views/ModalSendTestMail';
import SMTPSettingsTpl from 'app/modules/settings/templates/SMTPSettings.tpl';
import 'validator';

const SMTPSettings = View.extend({
    template: _.template(SMTPSettingsTpl),
    templateContext: {
        ...i18n,
        ChannelEncryption: ChannelEncryption
    },
    className: 'panel-body',
    submitTestMail: false, // Indicates whether the test mail dialog should be invoked after a form submit event
    events: {
        'click button.saveSettings': function(e) {
            e.preventDefault();
            this.submitTestMail = false;
            this.$el.find('form').trigger('submit');
        },
        'click button.sendTestMail': function(e) {
            e.preventDefault();
            this.enableSection();
            if(this.isFormValid()) {
                this.submitTestMail = true;
                this.$el.find('form').trigger('submit');
            }
            this.disableSection();
        },
        'click button.reset': function() {
            this.$el.find('form').trigger('reset');
            this.resetDropdownsFromModel();
        }
    },
    onRender: function() {
        var view = this;
        this.resetDropdownsFromModel();
        // Submission handler
        this.$el.find('form').validator().on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                if(view.submitTestMail) {
                    var smtpModel = new Backbone.Model();
                    smtpModel.set(view.getFormData());
                    radio.request('view:modal').show(new ModalSendTestMail({model: smtpModel}));
                } else {
                    view.model.save(view.getFormData(), {
                        success: function () {
                            radio.request('view:modal').show(new ModalSettingsSaveView());
                        }
                    });
                }
            }
        });
    },
    isFormValid: function() {
        var $form = this.$el.find('form');
        return !$form.validator('validate').has('.has-error').length;
    },
    enableSection: function() {
        this.$el.find('input[name="smtpServer"], input[name="smtpPort"], input[name="smtpFrom"]')
            .attr('required', true);
    },
    disableSection: function() {
        this.$el.find('input[name="smtpServer"], input[name="smtpPort"], input[name="smtpFrom"]')
            .attr('required', false);
    },
    getFormData: function() {
        return {
            smtpServer: this.$el.find('input[name="smtpServer"]').val(),
            smtpPort: parseInt(this.$el.find('input[name="smtpPort"]').val()),
            smtpEncryption: parseInt(this.$el.find('select[name="smtpEncryption"]').val()),
            smtpFrom: this.$el.find('input[name="smtpFrom"]').val(),
            smtpUser: this.$el.find('input[name="smtpUser"]').val(),
            smtpPassword: this.$el.find('input[name="smtpPassword"]').val()
        }
    },
    resetDropdownsFromModel: function() {
        this.$el.find('select[name="smtpEncryption"] option[value="' + this.model.get('smtpEncryption') + '"]').prop('selected', true);
    }
});

export default SMTPSettings;
