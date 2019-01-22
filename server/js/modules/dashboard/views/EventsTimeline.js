define(['app/app',
        'tpl!app/modules/dashboard/templates/EventsTimeline.tpl',
        'chart'],
function(HoneySens, EventsTimelineTpl) {
    HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
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
                    1: 'Januar', 2: 'Februar', 3: 'März', 4: 'April', 5: 'Mai', 6: 'Juni', 7: 'Juli',
                    8: 'August', 9: 'September', 10: 'Oktober', 11: 'November', 12: 'Dezember'};
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

        Views.EventsTimeline = Marionette.ItemView.extend({
            template: EventsTimelineTpl,
            className: 'panel panel-primary',
            onRender: function() {
                var view = this;
                this.listenTo(this.model, 'change', function() {
                    var view = this;
                    function performUpdate() {
                        var dataset = getDataset(view.model);
                        view.timeline.data.datasets[0].data = _.pluck(dataset, 'events');
                        view.timeline.data.labels = _.pluck(dataset, 'name');
                        view.timeline.update();
                    }

                    if(!this.timeline) setTimeout(performUpdate, 100);
                    else performUpdate();
                });
            },
            onShow: function() {
                var view = this,
                    $timeline = this.$el.find('#timeline'),
                    dataset = getDataset(this.model);
                setTimeout(function() {
                    view.timeline = new Chart($timeline, {
                        type: 'bar',
                        data: {
                            labels: _.pluck(dataset, 'name'),
                            datasets: [{
                                label: 'Ereignisse',
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
                }, 100);
            }
        });
    });

    return HoneySens.Dashboard.Views.EventsTimeline;
});