import HoneySens from 'app/app';
import MenuView from 'app/common/views/Menu';
import SidebarTpl from 'app/templates/Sidebar.tpl';

HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Sidebar = Marionette.LayoutView.extend({
        template: _.template(SidebarTpl),
        regions: {
            content: 'div.sidebar-content'
        },
        className: 'sidebar',
        events: {
            'mouseenter': function() {
                this.$el.addClass('expanded');
            },
            'mouseleave': function() {
                this.$el.removeClass('expanded');
            },
            'click a.toggle': function() {
                localStorage.setItem('sidebarExpanded', localStorage.getItem('sidebarExpanded') === 'true' ? 'false' : 'true');
                this.refreshSidebarExpansion();
                // Bit of a hack: allow other resizable components to readjust
                $(window).trigger('resize');
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
            this.refreshSidebarExpansion();
        },
        templateHelpers: {
            showVersion: function() {
                return HoneySens.data.system.get('version');
            }
        },
        refreshSidebarExpansion: function() {
            var sidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true',
                iconName = sidebarExpanded ? 'resize-small' : 'resize-full',
                $sidebar = $('div#sidebar');
            this.$el.find('a.toggle').html('<span class="glyphicon glyphicon-' + iconName + '"></span>')
            if(sidebarExpanded) $sidebar.addClass('expanded');
            else $sidebar.removeClass('expanded');
        }
    });
});

export default HoneySens.Views.Sidebar;
