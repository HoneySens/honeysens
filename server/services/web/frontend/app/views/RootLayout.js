import Marionette from 'backbone.marionette';
import Regions from 'app/views/regions';
import RootLayoutTpl from 'app/templates/RootLayout.tpl';

export default Marionette.LayoutView.extend({
    el: 'body',
    template: _.template(RootLayoutTpl),
    regions: {
        navigation: 'nav.navbar',
        content: '#content'
    },
    onRender: function() {
        this.addRegion('modal', new Regions.ModalRegion({el: '#modal'}));
    }
});