define(['app/app',
        'app/views/regions',
        'tpl!app/templates/AppLayout.tpl'],
function(HoneySens, Regions, AppLayoutTpl) {
    HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.AppLayout = Marionette.LayoutView.extend({
            template: AppLayoutTpl,
            regions: {
                navigation: 'nav.navbar',
                sidebar: '#sidebar',
                main: '#main',
                overlay: {selector: '#overlay', regionClass: Regions.OverlayRegion}
            }
        });
    });

    return HoneySens.Views.AppLayout;
});