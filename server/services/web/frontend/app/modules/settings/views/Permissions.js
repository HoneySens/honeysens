import HoneySens from 'app/app';
import PermissionsTpl from 'app/modules/settings/templates/Permissions.tpl';

HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Permissions = Marionette.ItemView.extend({
        template: _.template(PermissionsTpl),
        className: 'panel-body',
        events: {
            'change input[type="checkbox"][name="preventEventDeletionByManagers"]': function(e) {
                this.model.save({preventEventDeletionByManagers: e.target.checked});
            },
            'change input[type="checkbox"][name="preventSensorDeletionByManagers"]': function(e) {
                this.model.save({preventSensorDeletionByManagers: e.target.checked});
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

export default HoneySens.Settings.Views.Permissions;