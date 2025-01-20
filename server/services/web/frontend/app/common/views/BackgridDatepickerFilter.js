define(['app/app',
        'app/common/templates/BackgridDatepickerFilter.tpl',
        'bootstrap-datepicker'],
function(HoneySens, BackgridDatepickerFilterTpl) {
    HoneySens.module('Common.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.BackgridDatepickerFilter = Backbone.View.extend({
            tagName: 'div',
            className: 'input-group input-daterange',
            template: _.template(BackgridDatepickerFilterTpl),
            events: {
                'change': 'onChange'
            },
            defaults: {
                fromField: undefined,
                toField: undefined
            },
            initialize: function (options) {
                _.defaults(this, options || {}, this.defaults);
                if (_.isEmpty(this.fromField) || !this.fromField.length) throw 'Invalid or missing fromField.';
                if (_.isEmpty(this.toField) || !this.toField.length) throw 'Invalid or missing toField.';
                this.serverSide = (Backbone.PageableCollection) && (this.collection instanceof Backbone.PageableCollection) && (this.collection.mode !== "client")
                this.collection = this.collection.fullCollection || this.collection;
            },
            render: function () {
                this.$el.empty().append(this.template({}));
                // Initialize date range picker
                this.$el.datepicker({clearBtn: true});
                this.onChange();
                return this;
            },
            onChange: function () {
                var col = this.collection,
                    valueFrom = this.$el.find('input.dpFrom').datepicker('getDate'),
                    valueTo = this.$el.find('input.dpTo').datepicker('getDate');
                if(this.serverSide) {
                    // If any side of the range isn't specified, don't send that query parameter (equals oldest/newest event)
                    if(valueFrom == null) {
                        delete col.queryParams[this.fromField];
                    } else {
                        // Set the daytime so that both days at the end of the range are included
                        valueFrom.setHours(0);
                        valueFrom.setMinutes(0);
                        col.queryParams[this.fromField] = valueFrom.getTime() / 1000;
                    }
                    if(valueTo == null) {
                        delete col.queryParams[this.toField];
                    } else {
                        // Set the daytime so that both days at the end of the range are included
                        valueTo.setHours(23);
                        valueTo.setMinutes(59);
                        col.queryParams[this.toField] = valueTo.getTime() / 1000;
                    }
                    col.getFirstPage();
                } else {
                    // Support for client-side filtering is currently not implemented
                }
            }
        });
    });

    return HoneySens.Common.Views.BackgridDatepickerFilter;
});