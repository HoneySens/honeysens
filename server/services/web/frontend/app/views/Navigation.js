import HoneySens from 'app/app';
import MenuView from 'app/common/views/Menu';
import NavigationTpl from 'app/templates/Navigation.tpl';
import ProgressBar from 'progressbar.js';
import i18n from 'app/common/i18n';

HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Navigation = Marionette.LayoutView.extend({
        popoverVisible: false,
        template: _.template(NavigationTpl),
        className: 'container-fluid',
        regions: {
            menu: 'div.navbar-menu'
        },
        events: {
            'click li a': function() { this.$el.find('div.navbar-collapse').collapse('hide'); },
            'mouseenter #counter': function(e) {
                e.preventDefault();
                // The counter widget sends mouseenter events on every new counter cycle. These events and the
                // popoverVisible variable are used to avoid unwanted popover refreshes while it is already shown
                if(!this.popoverVisible) {
                    this.$el.find('#counter').popover('show');
                    this.popoverVisible = true;
                }
            },
            'mouseleave #counter': function(e) {
                e.preventDefault();
                this.$el.find('#counter').popover('hide');
                this.popoverVisible = false;
            },
            'click a#langEnglish': async function(e) {
                e.preventDefault();
                await i18n.setLanguage('en');
                location.reload();
            },
            'click a#langGerman': async function(e) {
                e.preventDefault();
                await i18n.setLanguage('de');
                location.reload();
            }
        },
        initialize: function() {
            var view = this;
            this.listenTo(HoneySens.vent, 'counter:started', function() {
                view.circle.set(0);
                view.circle.animate(1.0, {
                    duration: 10000
                });
            });
            this.listenTo(HoneySens.vent, 'counter:updated', function(counter) {
                this.$el.find('div.popover div.popover-content span.counter').html(counter);
            });
        },
        onRender: function() {
            var view = this;
            this.menu.show(new MenuView({model: new Backbone.Model({items: HoneySens.menuItems})}));
            this.$el.find('#counter').popover({
                html: true,
                content: function() {
                    return view.$el.find('div.popover div.popover-content').html();
                },
                placement: 'bottom',
                title: _.t('layout:updateHeader'),
                trigger: 'manual',
                container: this.$el
            });
            this.$el.find('.dropdown').hover(
                function() {
                    $(this).addClass('open');
                },
                function() {
                    $(this).removeClass('open');
                });
        },
        onShow: function() {
            this.circle = new ProgressBar.Circle('#counter', {
                color: '#777',
                strokeWidth: 20,
                trailWidth: 1
            });
        },
        templateHelpers: {
            getLanguage: function() {
                return i18n.getLanguage();
            }
        }
    });
});

export default HoneySens.Views.Navigation;