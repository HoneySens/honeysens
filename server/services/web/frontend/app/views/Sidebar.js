import { View } from 'backbone.marionette';
import Backbone from 'backbone';
import HoneySens from 'app/app';
import MenuView from 'app/common/views/Menu';
import SidebarTpl from 'app/templates/Sidebar.tpl';

const Sidebar = View.extend({
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
        this.listenTo(Backbone.history, 'route', function(router, route, params) {
            var $sidebar = this.$el;
            if(router.current) {
                var fragment = '#' + router.current().fragment;
                $sidebar.find('ul.nav-sidebar li > a').each(function() {
                    if(fragment.startsWith($(this).attr('href'))) {
                        var $node = $(this).parent('li').addClass('active');
                        $sidebar.find('ul.nav-sidebar li').not($node).removeClass('active');
                    }
                });
            }
        });
    },
    onRender: function() {
        this.getRegion('content').show(new MenuView({model: new Backbone.Model({items: HoneySens.menuItems})}));
        this.refreshSidebarExpansion();
    },
    templateContext: {
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

export default Sidebar;
