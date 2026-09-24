import { radio } from 'app/radio';
import HoneySens from 'app/app';
import DivisionsItemView from 'app/modules/accounts/views/DivisionsItemView';
import DivisionsListViewTpl from 'app/modules/accounts/templates/DivisionsListView.tpl';

HoneySens.module('Accounts.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.DivisionsListView = Marionette.CollectionView.extend({
        template: _.template(DivisionsListViewTpl),
        childViewContainer: 'tbody',
        childView: DivisionsItemView,
        events: {
            'click #addDivision': function(e) {
                e.preventDefault();
                radio.request('accounts:division:add', {animation: 'slideLeft'});
            }
        }
    });
});

export default HoneySens.Accounts.Views.DivisionsListView;