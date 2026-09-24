import { View } from 'backbone.marionette';
import ClassificationBreakdownTpl from 'app/modules/dashboard/templates/ClassificationBreakdown.tpl';
import 'chart.js';

// Calculates the pie chart dataset from model data
function getDataset(model) {
    var dataset = [],
        classificationData = _.unzip(_.map(model.get('classification'), function(d) {return [parseInt(d.events), parseInt(d.classification)]})),
        classificationDict = {
            '0': {'label': _.t('unknown'), 'color': '#474949'},
            '2': {'label': _.t('eventClassificationConnectionAttempt'), 'color': '#ddd'},
            '3': {'label': _.t('eventClassificationHoneypot'), 'color': '#d9230f'},
            '4': {'label': _.t('eventClassificationScan'), 'color': '#029acf'}
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

const ClassificationBreakdownView = View.extend({
    template: _.template(ClassificationBreakdownTpl),
    className: 'panel panel-primary',
    onRender: function() {
        this.listenTo(this.model, 'change', function() {
            if(!this.pieChart) return;
            var dataset = getDataset(this.model);
            this.pieChart.data.datasets[0].data = _.pluck(dataset, 'events');
            this.pieChart.update();
        });
        this.initPieChart();
    },
    initPieChart: function() {
        var view = this,
            $classificationBreakdown = this.$el.find('#classificationBreakdown'),
            dataset = getDataset(view.model);
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
    }
});

export default ClassificationBreakdownView;