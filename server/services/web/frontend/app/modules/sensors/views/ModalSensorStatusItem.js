import { View } from 'backbone.marionette';
import ModalSensorStatusItemTpl from 'app/modules/sensors/templates/ModalSensorStatusItem.tpl';

const ModalSensorStatusItem = View.extend({
    template: _.template(ModalSensorStatusItemTpl),
    tagName: 'tr',
    templateContext: {
        showTimestamp: function() {
            var ts = this.timestamp;
            return ('0' + ts.getDate()).slice(-2) + '.' + ('0' + (ts.getMonth() + 1)).slice(-2) + '.' +
                ts.getFullYear() + ' ' + ('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2);
        }
    }
});

export default ModalSensorStatusItem;
