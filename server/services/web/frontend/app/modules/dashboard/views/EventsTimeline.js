import { View } from 'backbone.marionette';
import EventsTimelineTpl from 'app/modules/dashboard/templates/EventsTimeline.tpl';
import 'chart.js';

// Calculates the timeline dataset from model data
function getDataset(model) {
    var dataset = [],
        ticks = 0,
        eventsPerTick = _.unzip(_.map(model.get('events_timeline'), function(d) {return [parseInt(d.events), parseInt(d.tick)]})),
        tickDict = {};

    if(model.get('month')) {
        ticks = (new Date(model.get('year'), model.get('month'), 0)).getDate();
        for(var i=1;i<=ticks;i++) {
            tickDict[i] = i;
        }
    } else {
        ticks = 12;
        tickDict = {
            1: _.t('january'), 2: _.t('february'), 3: _.t('march'), 4: _.t('april'), 5: _.t('may'),
            6: _.t('june'), 7: _.t('july'), 8: _.t('august'), 9: _.t('september'), 10: _.t('october'),
            11: _.t('november'), 12: _.t('december')};
    }

    for(var i=1;i<=ticks;i++) {
        if(_.contains(eventsPerTick[1], i)) {
            var index = _.indexOf(eventsPerTick[1], i);
            dataset.push({'name': tickDict[i], 'events': eventsPerTick[0][index]});
        } else {
            dataset.push({'name': tickDict[i], 'events': 0});
        }
    }
    return dataset;
}

const EventsTimelineView = View.extend({
    template: _.template(EventsTimelineTpl),
    className: 'panel panel-primary',
    onRender: function() {
        this.listenTo(this.model, 'change', function() {
            if(!this.timeline) return;
            var dataset = getDataset(this.model);
            this.timeline.data.datasets[0].data = _.pluck(dataset, 'events');
            this.timeline.data.labels = _.pluck(dataset, 'name');
            this.timeline.update();
        });
        this.initTimeline();
    },
    initTimeline: function() {
        var view = this,
            $timeline = this.$el.find('#timeline'),
            dataset = getDataset(view.model);
        view.timeline = new Chart($timeline, {
            type: 'bar',
            data: {
                labels: _.pluck(dataset, 'name'),
                datasets: [{
                    label: _.t('events'),
                    data: _.pluck(dataset, 'events'),
                    backgroundColor: '#d9230f'
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                legend: {
                    display: false
                }
            }
        });
    }
});

export default EventsTimelineView;