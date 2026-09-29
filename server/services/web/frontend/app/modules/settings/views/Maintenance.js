import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import Backbone from 'backbone';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import ModalConfirmation from 'app/common/views/ModalConfirmation';
import MaintenanceTpl from 'app/modules/settings/templates/Maintenance.tpl';
import { EventTemplateHelpers } from 'app/views/common';

const DatabaseSettings = View.extend({
    template: _.template(MaintenanceTpl),
    className: 'col-sm-12',
    events: {
        'click button.resetDB': function () {
            var view = this;
            view.$el.find('button.resetDB').button('loading');
            $.ajax({
                type: 'DELETE',
                url: 'api/system/db',
                success: function () {
                    radio.request('logout');
                }
            });
        },
        'click button.removeEvents': function () {
            radio.request('view:modal').show(new ModalConfirmation({
                model: new Backbone.Model({
                    msg: i18n.t('settings:removeAllEventsPrompt'),
                    onConfirm: function () {
                        $.ajax({
                            type: 'DELETE',
                            url: 'api/system/events',
                            success: function () {
                                HoneySens.data.models.events.reset();
                                radio.request('view:modal').empty();
                            }
                        });
                    }
                })
            }));
        },
        'click button.refreshCA': function () {
            let modal = radio.request('view:modal'),
                view = this;
            radio.request('view:modal').show(new ModalConfirmation({
                model: new Backbone.Model({
                    msg: i18n.t('settings:internalCAPrompt'),
                    onConfirm: function () {
                        modal.$el.find('button.btn-primary').text('Bitte warten');
                        modal.$el.find('button').prop('disabled', true);
                        $.ajax({
                            type: 'PUT',
                            url: 'api/system/ca',
                        });
                        // Reload the whole page if a new CA was activated after a short timeout (to allow the server to restart)
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    }
                })
            }));
        },
    },
    templateContext: {
        ...i18n,
        showCaFP: function() {
            return this.caFP.replace(/(..?)/g, '$1:').slice(0, -1)
        },
        showCaExpire: function() {
            return EventTemplateHelpers.showTimestamp(this.caExpire);
        }
    }
});

export default DatabaseSettings;
