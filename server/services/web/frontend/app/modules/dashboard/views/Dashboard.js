import HoneySens from 'app/app';
import EventsTimelineView from 'app/modules/dashboard/views/EventsTimeline';
import ClassificationBreakdownView from 'app/modules/dashboard/views/ClassificationBreakdown';
import SummaryView from 'app/modules/dashboard/views/Summary';
import DashboardTpl from 'app/modules/dashboard/templates/Dashboard.tpl';

HoneySens.module('Dashboard.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    // inline views to render the division selector
    var DivisionDropdownItem = Marionette.ItemView.extend({
        template: _.template('<%- name %>'),
        tagName: 'option',
        onRender: function() {
            this.$el.attr('value', this.model.id);
        }
    });

    var DivisionDropdownView = Marionette.CollectionView.extend({
        template: false,
        childView: DivisionDropdownItem,
        events: {
            'change': function() {
                HoneySens.request('dashboard:filter:division', this.$el.val());
            }
        }
    });

    Views.Dashboard = Marionette.LayoutView.extend({
        template: _.template(DashboardTpl),
        className: 'dashboard',
        events: {
            'click button.yearDec': function(e) {
                e.preventDefault();
                this.model.fetch({data: {year: parseInt(this.model.get('year')) - 1, month: this.model.get('month'), division: this.model.get('division')}});
            },
            'click button.yearInc': function(e) {
                e.preventDefault();
                this.model.fetch({data: {year: parseInt(this.model.get('year')) + 1, month: this.model.get('month'), division: this.model.get('division')}});
            },
            'change select.monthFilter': function(e) {
                this.model.fetch({data: {year: parseInt(this.model.get('year')), month: $(e.target).val(), division: this.model.get('division')}});
            }
        },
        regions: {
            eventsTimeline: 'div.eventsTimeline',
            classificationBreakdown: 'div.classificationBreakdown',
            summary: 'div.summary'
        },
        initialize: function() {
            var view = this;
            view.model.fetch();
            HoneySens.reqres.setHandler('dashboard:filter:division', function(id) {
                view.model.fetch({data: {year: parseInt(view.model.get('year')), month: view.model.get('month'), division: id}});
            });
            this.listenTo(HoneySens.vent, 'models:updated', function() {
                view.model.fetch({data: {year: parseInt(view.model.get('year')), month: view.model.get('month'), division: this.model.get('division')}});
            });
        },
        onRender: function() {
            var divisionDropdownView = new DivisionDropdownView({el: this.$el.find('select.divisionFilter'),
                collection: HoneySens.data.models.divisions
            });
            divisionDropdownView.render();
            this.$el.find('input.yearFilter').val(this.model.get('year'));
            this.getRegion('eventsTimeline').show(new EventsTimelineView({model: this.model}));
            this.getRegion('classificationBreakdown').show(new ClassificationBreakdownView({model: this.model}));
            this.getRegion('summary').show(new SummaryView({model: this.model}));
            this.listenTo(this.model, 'change', function() {
                this.$el.find('input.yearFilter').val(this.model.get('year'));
            });
        }
    });
});

export default HoneySens.Dashboard.Views.Dashboard;