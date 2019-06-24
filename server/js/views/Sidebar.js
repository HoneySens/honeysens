define(['app/app',
        'app/common/views/Menu',
        'tpl!app/templates/Sidebar.tpl'],
function(HoneySens, MenuView, SidebarTpl) {
    HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Sidebar = Marionette.LayoutView.extend({
            template: SidebarTpl,
            regions: {
                content: 'div.sidebar-content'
            },
            events: {
                'mouseenter': function() {
                    this.$el.addClass('expanded');
                },
                'mouseleave': function() {
                    this.$el.removeClass('expanded');
                }
            },
            initialize: function() {
                // Match routes with sidebar highlighting
                // TODO consider using Marionette AppRouter to get the current fragment more easily
                this.listenTo(Backbone.history, 'route', function(router, route, params) {
                    var $sidebar = this.$el;
                    if(router.current) {
                        var fragment = router.current().fragment;
                        $sidebar.find('ul.nav-sidebar li > a').each(function() {
                            if($(this).attr('href') == '#' + fragment) {
                                var $node = $(this).parent('li').addClass('active');
                                $sidebar.find('ul.nav-sidebar li').not($node).removeClass('active');
                            }
                        });
                    }
                });
            },
            onRender: function() {
                this.content.show(new MenuView({model: new Backbone.Model({items: HoneySens.menuItems})}));
            },
            templateHelpers: {
                showVersion: function() {
                    return HoneySens.data.system.get('version');
                }
            }
        });
    });

    return HoneySens.Views.Sidebar;
});
