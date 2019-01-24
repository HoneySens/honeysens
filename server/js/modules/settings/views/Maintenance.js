define(['app/app',
        'tpl!app/modules/settings/templates/Maintenance.tpl',
        'app/views/common'],
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
                'click button.refreshCA': function() {
                    var view = this;
                    this.$el.find('button.refreshCA').button('loading');
                    $.ajax({
                        type: 'PUT',
                        url: 'api/system/ca',
                        success: function() {
                            view.model.fetch({
                                success: function() {
                                    view.render();
                                }
                            });
                        }
                    });
                    // Reload the whole page if a new CA was activated after a short timeout (to allow the server to restart)
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
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

    return HoneySens.Settings.Views.DatabaseSettings;
});
