define(['app/app', 'app/modules/accounts/views/DivisionsItemView',
        'tpl!app/modules/accounts/templates/DivisionsListView.tpl'],
function(HoneySens, DivisionsItemView, DivisionsListViewTpl) {
    HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.DivisionsListView = Marionette.CompositeView.extend({
            template: DivisionsListViewTpl,
            childViewContainer: 'tbody',
            childView: DivisionsItemView,
            events: {
                'click #addDivision': function(e) {
                    e.preventDefault();
                    HoneySens.request('accounts:division:add', {animation: 'slideLeft'});
                }
            }
        });
    });

    return HoneySens.Accounts.Views.DivisionsListView;
});