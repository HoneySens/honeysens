define(['app/app',
        'tpl!app/templates/Sidebar.tpl',
        'app/views/common'],
function(HoneySens, SidebarTpl) {
    HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Sidebar = Marionette.ItemView.extend({
            template: SidebarTpl,
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
                this.$el.find('ul.nav-sidebar').append(Views.createMenu(HoneySens.menuItems));
            },
            templateHelpers: {
                showVersion: function() {
                    // Only show the major and minor version numbers
                    version = HoneySens.data.system.get('version').split('.');
                    return version[0] + '.' + version[1];
                }
            }
        });
    });

    return HoneySens.Views.Sidebar;
});
