import HoneySens from 'app/app';

HoneySens.module('Common.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    // Creates a menu from the global application menu data (and therefore from all submodules)
    Views.Menu = Marionette.ItemView.extend({
        tagName: 'ul',
        className: 'nav nav-sidebar',
        getTemplate: function() {
            return _.template(this.createMenuPart(this.model.get('items')));
        },
        createMenuPart: function(items) {
            var result = '',
                view = this;
            $.each(items, function() {
                var subitems = '';
                if(this.hasOwnProperty('items')) {
                    subitems = '<ul class="nav">' + view.createMenuPart(this.items) + '</ul>';
                }
                if(HoneySens.assureAllowed(this.permission.domain, this.permission.action)) {
                    result += '<li><a href="#' + this.uri + '"><span class="' + this.iconClass + '"></span><span class="badge"></span><span class="menuLabel">&nbsp;&nbsp;' + this.title + '</span></a>' + subitems + '</li>';
                }
            });
            return result;
        },
        onRender: function() {
            var view = this;
            // Add a badge to menu entries with highlighted items and listen for changes
            $.each(this.model.get('items'), function() {
                var item = this;
                if(this.hasOwnProperty('highlight')) {
                    view.listenTo(item.highlight.getModel(), item.highlight.event, function() {
                        view.refreshHighlight(item.uri, item.highlight.count());
                    });
                    view.refreshHighlight(item.uri, item.highlight.count());
                }
            });
        },
        refreshHighlight: function(uri, count) {
            // Hide the badge if there are no highlights
            this.$el.find('a[href="#' + uri + '"] span.badge').text(count === 0 ? '' : count);
        }
    });
});

export default HoneySens.Common.Views.Menu;