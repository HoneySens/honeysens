import i18n from 'app/common/i18n';
import { ChannelEncryption } from 'app/models';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import ModalSettingsSaveView from 'app/modules/settings/views/ModalSettingsSave';
import LDAPTpl from 'app/modules/settings/templates/LDAP.tpl';
import 'validator';

const LDAP = View.extend({
    template: _.template(LDAPTpl),
    templateContext: {
        ...i18n,
        ChannelEncryption: ChannelEncryption
    },
    className: 'panel-body',
    events: {
        'click button.reset': function() {
            this.$el.find('form').trigger('reset');
        }
    },
    onRender: function() {
        var view = this;
        // Set LDAP encryption from model
        this.$el.find('select[name="ldapEncryption"] option[value="' + this.model.get('ldapEncryption') + '"]').prop('selected', true);
        // Enable help popovers
        this.$el.find('[data-toggle="popover"]').popover();
        // Submission handler
        this.$el.find('form').validator().on('submit', function (e) {
            if(!e.isDefaultPrevented()) {
                e.preventDefault();
                view.model.save(view.getFormData(), {
                    success: function() {
                        radio.request('view:modal').show(new ModalSettingsSaveView());
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
            ldapServer: this.$el.find('input[name="ldapServer"]').val(),
            ldapPort: parseInt(this.$el.find('input[name="ldapPort"]').val()),
            ldapEncryption: parseInt(this.$el.find('select[name="ldapEncryption"]').val()),
            ldapTemplate: this.$el.find('input[name="ldapTemplate"]').val()
        }
    }
});

export default LDAP;
