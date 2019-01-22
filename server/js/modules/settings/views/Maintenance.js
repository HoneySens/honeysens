define(['app/app',
        'tpl!app/modules/settings/templates/Maintenance.tpl'],
function(HoneySens, MaintenanceTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.DatabaseSettings = Marionette.ItemView.extend({
            template: MaintenanceTpl,
            className: 'col-sm-12',
            events: {
                'click button.resetDB': function () {
                    var view = this;
                    view.$el.find('button.resetDB').button('loading');
                    $.ajax({
                        type: 'DELETE',
                        url: 'api/system/db',
                        success: function() {
                            HoneySens.execute('logout');
                        }
                    });
                },
                'click button.removeEvents': function () {
                    var view = this;
                    this.$el.find('button.removeEvents').button('loading');
                    $.ajax({
                        type: 'DELETE',
                        url: 'api/system/events',
                        success: function() {
                            HoneySens.data.models.events.reset();
                            view.$el.find('button.removeEvents').text('Erledigt');
                        }
                    });
                },
                'click button.updateDB': function() {
                    var view = this;
                    this.$el.find('button.updateDB').button('loading');
                    $.ajax({
                        type: 'PUT',
                        url: 'api/system/db',
                        success: function() {
                            HoneySens.execute('logout');
                        }
                    });
                }
            }
        });
    });

    return HoneySens.Settings.Views.DatabaseSettings;
});
