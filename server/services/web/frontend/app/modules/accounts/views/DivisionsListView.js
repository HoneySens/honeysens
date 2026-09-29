import i18n from 'app/common/i18n';
import { CollectionView } from 'backbone.marionette';
import { radio } from 'app/radio';
import DivisionsItemView from 'app/modules/accounts/views/DivisionsItemView';
import DivisionsListViewTpl from 'app/modules/accounts/templates/DivisionsListView.tpl';

const DivisionsListView = CollectionView.extend({
    template: _.template(DivisionsListViewTpl),
    templateContext: {...i18n},
    childViewContainer: 'tbody',
    childView: DivisionsItemView,
    events: {
        'click #addDivision': function(e) {
            e.preventDefault();
            radio.request('accounts:division:add', {animation: 'slideLeft'});
        }
    }
});

export default DivisionsListView;
