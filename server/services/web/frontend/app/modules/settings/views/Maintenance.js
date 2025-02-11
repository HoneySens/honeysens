import HoneySens from 'app/app';
import ModalConfirmation from 'app/common/views/ModalConfirmation';
import MaintenanceTpl from 'app/modules/settings/templates/Maintenance.tpl';
import 'app/views/common';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.DatabaseSettings = Marionette.ItemView.extend({
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
                        HoneySens.execute('logout');
                    }
                });
            },
            'click button.removeEvents': function () {
                HoneySens.request('view:modal').show(new ModalConfirmation({
                    model: new Backbone.Model({
                        msg: _.t('settings:removeAllEventsPrompt'),
                        onConfirm: function () {
                            $.ajax({
                                type: 'DELETE',
                                url: 'api/system/events',
                                success: function () {
                                    HoneySens.data.models.events.reset();
                                    HoneySens.request('view:modal').empty();
                                }
                            });
                        }
                    })
                }));
            },
            'click button.refreshCA': function () {
                let modal = HoneySens.request('view:modal'),
                    view = this;
                HoneySens.request('view:modal').show(new ModalConfirmation({
                    model: new Backbone.Model({
                        msg: _.t('settings:internalCAPrompt'),
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
        templateHelpers: {
            showCaFP: function() {
                return this.caFP.replace(/(..?)/g, '$1:').slice(0, -1)
            },
            showCaExpire: function() {
                return HoneySens.Views.EventTemplateHelpers.showTimestamp(this.caExpire);
            }
        }
    });
});

export default HoneySens.Settings.Views.DatabaseSettings;