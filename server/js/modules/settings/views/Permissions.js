define(['app/app',
        'tpl!app/modules/settings/templates/Permissions.tpl'],
function(HoneySens, PermissionsTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Permissions = Marionette.ItemView.extend({
            template: PermissionsTpl,
            className: 'panel-body',
            events: {
                'change input[type="checkbox"][name="restrictManagers"]': function(e) {
                    this.model.save({restrictManagerRole: e.target.checked});
                },
                'change input[type="checkbox"][name="requireEventComment"]': function(e) {
                    this.model.save({requireEventComment: e.target.checked});
                },
                'change input[type="checkbox"][name="requireFilterDescription"]': function(e) {
                    this.model.save({requireFilterDescription: e.target.checked});
                }
            }
        });
    });

    return HoneySens.Settings.Views.Permissions;
});