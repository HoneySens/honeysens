import i18n from 'app/common/i18n';
import HoneySens from 'app/app';
import { EventClassification, TaskStatus } from 'app/models';
import { CollectionView, View } from 'backbone.marionette';
import { Spinner } from 'spin.js';

export const EventTemplateHelpers = {
    showTimestamp: function (ts) {
        var ts = ts || this.timestamp;
        ts = new Date(ts * 1000);
        return ('0' + ts.getDate()).slice(-2) + '.' + ('0' + (ts.getMonth() + 1)).slice(-2) + '.' +
            ts.getFullYear() + ' ' + ('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2);
    },
    showClassification: function (classification) {
        var classification = classification || this.classification;
        switch (classification) {
            case EventClassification.ICMP:
                return i18n.t('eventClassificationICMP');
            case EventClassification.CONN_ATTEMPT:
                return i18n.t('eventClassificationConnectionAttempt');
            case EventClassification.LOW_HP:
                return i18n.t('eventClassificationHoneypot');
            case EventClassification.PORTSCAN:
                return i18n.t('eventClassificationScan');
            default:
                return i18n.t('unknown');
        }
    },
    showSensor: function (eventAttrs) {
        eventAttrs = eventAttrs || this;
        // On archived events, the sensor name is directly attached to the event
        if(eventAttrs.archived) return eventAttrs.sensor;
        else return HoneySens.data.models.sensors.get(eventAttrs.sensor).get('name');
    },
    showDivisionForEvent: function(eventAttrs) {
        eventAttrs = eventAttrs || this;
        if(eventAttrs.archived) {
            // On archived events, the division is usually a reference or. In case it doesn't exist anymore,
            // division is null and its last name can be found in divisionName.
            if(eventAttrs.division === null) {
                return eventAttrs.divisionName;
            } else {
                return HoneySens.data.models.divisions.get(eventAttrs.division).get('name');
            }
        } else return EventTemplateHelpers.showDivision(eventAttrs.sensor);
    },
    showDivision: function(sensor) {
        var sensor = sensor || this.sensor;
        var division_id =  HoneySens.data.models.sensors.get(sensor).get('division');
        return HoneySens.data.models.divisions.get(division_id).get('name');
    },
    showSummary: function (summary, numberOfPackets, numberOfDetails) {
        var summary = summary || this.summary;
        var interactionCount;
        interactionCount = parseInt(numberOfPackets) + parseInt(numberOfDetails);
        summary = summary + ' (' + interactionCount + ')';
        return summary;
    }
};

// Initialize spinner views
export const spinner = new Spinner({lines: 13, length: 4, width: 2, radius: 6, corners: 1, rotate: 0, direction: 1, color: '#000',
    speed: 1, trail: 60, shadow: false, hwaccel: false, className: 'spinner', zIndex: 2e9, top: '50%', left: '50%'});
export const inlineSpinner = new Spinner({lines: 10, length: 3, width: 2, radius: 4, corners: 1, rotate: 0, direction: 1, color: '#000',
    speed: 1, trail: 60, shadow: false, hwaccel: false, className: 'spinner', zIndex: 2e9, top: '50%', left: '50%'});

// Periodically checks status for the given task model on the server, optionally executes a callback when the
// task is done or if there was an error during a request (parameters 'done' and 'error' of the options object).
export function waitForTask(task, options) {
    options = options || {};
    var status = task.get('status');
    if(status === TaskStatus.SCHEDULED || status === TaskStatus.RUNNING) {
        setTimeout(function() {
            task.fetch({
                success: function(model) {
                    waitForTask(model, options);
                },
                error: function(model, resp) {
                    if(options.hasOwnProperty('error')) options.error(model, resp);
                }
            });
        }, 1000);
    } else {
        if(status === TaskStatus.DONE && options.hasOwnProperty('done')) options.done(task);
        else if(status === TaskStatus.ERROR && options.hasOwnProperty('error')) options.error(task, null);
    }
}
