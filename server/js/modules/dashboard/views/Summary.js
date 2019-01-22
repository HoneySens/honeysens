define(['app/app',
        'app/modules/dashboard/views/EventsTimeline',
        'app/modules/dashboard/views/ClassificationBreakdown',
        'tpl!app/modules/dashboard/templates/Summary.tpl'],
function(HoneySens, EventsTimelineView, ClassificationBreakdownView, SummaryTpl) {
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

        Views.Summary = Marionette.LayoutView.extend({
            template: SummaryTpl,
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
                classificationBreakdown: 'div.classificationBreakdown'
            },
            initialize: function() {
                var view = this;
                HoneySens.reqres.setHandler('dashboard:filter:division', function(id) {
                    view.model.fetch({data: {year: parseInt(view.model.get('year')), month: view.model.get('month'), division: id}});
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
                this.listenTo(this.model, 'change', function() {
                    this.$el.find('input.yearFilter').val(this.model.get('year'));
                });
            }
        });
    });

    return HoneySens.Dashboard.Views.Summary;
});