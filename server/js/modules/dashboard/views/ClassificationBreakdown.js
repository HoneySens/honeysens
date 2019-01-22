define(['app/app',
        'tpl!app/modules/dashboard/templates/ClassificationBreakdown.tpl',
        'chart'],
function(HoneySens, ClassificationBreakdownTpl) {
    HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        // Calculates the pie chart dataset from model data
        function getDataset(model) {
            var dataset = [],
                classificationData = _.unzip(_.map(model.get('classification'), function(d) {return [parseInt(d.events), parseInt(d.classification)]})),
                classificationDict = {
                    '0': {'label': 'Unbekannt', 'color': '#474949'},
                    '2': {'label': 'Verbindungsversuch', 'color': '#ddd'},
                    '3': {'label': 'Honeypot', 'color': '#d9230f'},
                    '4': {'label': 'Scan', 'color': '#029acf'}
                };

            _.each([0, 2, 3, 4], function(i) {
                if(_.contains(classificationData[1], i)) {
                    var index = _.indexOf(classificationData[1], i);
                    dataset.push({'name': classificationDict[i].label, 'color': classificationDict[i].color, 'events': classificationData[0][index]});
                } else {
                    dataset.push({'name': classificationDict[i].label, 'color': classificationDict[i].color, 'events': 0});
                }
            });

            return dataset;
        }

        Views.ClassificationBreakdown = Marionette.ItemView.extend({
            template: ClassificationBreakdownTpl,
            className: 'panel panel-primary',
            onRender: function() {
                this.listenTo(this.model, 'change', function() {
                    var view = this;
                    function performUpdate() {
                        var dataset = getDataset(view.model);
                        view.pieChart.data.datasets[0].data = _.pluck(dataset, 'events');
                        view.pieChart.update();
                    }

                    if(!this.pieChart) setTimeout(performUpdate, 100);
                    else performUpdate();
                });
            },
            onShow: function() {
                var view = this,
                    $classificationBreakdown = this.$el.find('#classificationBreakdown'),
                    dataset = getDataset(this.model);
                setTimeout(function() {
                    view.pieChart = new Chart($classificationBreakdown, {
                        type: 'pie',
                        data: {
                            labels: _.pluck(dataset, 'name'),
                            datasets: [{
                                data: _.pluck(dataset, 'events'),
                                backgroundColor: _.pluck(dataset, 'color')
                            }]
                        },
                        options: {
                            legend: {
                                display: false
                            }
                        }
                    });
                    view.$el.find('#classificationBreakdownLegend').html(view.pieChart.generateLegend());
                }, 100);
            }
        });
    });

    return HoneySens.Dashboard.Views.ClassificationBreakdown;
});