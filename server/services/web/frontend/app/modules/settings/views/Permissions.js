import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import PermissionsTpl from 'app/modules/settings/templates/Permissions.tpl';

const Permissions = View.extend({
    template: _.template(PermissionsTpl),
    templateContext: {...i18n},
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

export default Permissions;
