define(['backbone.marionette',
        'app/views/regions',
        'app/templates/RootLayout.tpl'],
function(Marionette, Regions, RootLayoutTpl) {
    return Marionette.LayoutView.extend({
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
});