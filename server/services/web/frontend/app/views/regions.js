import { Region } from 'backbone.marionette';
import Backbone from 'backbone';
import _ from 'underscore';
import $ from 'jquery';
import 'bootstrap';

var Regions = {};

Regions.ModalRegion = Region.extend({
    constructor: function() {
        var region = this;
        Region.prototype.constructor.apply(this, arguments);
        this._ensureElement();
        this.$el.on('hidden.bs.modal', { region: this }, function(e) {
            e.data.region.empty();
        });
        this.$el.on('shown.bs.modal', function() {
            region.$el.find(':button[autofocus]').focus();
        });
    },
    onShow: function() {
        this.$el.modal('show');
    },
    onEmpty: function() {
        this.$el.modal('hide');
    }
});

Regions.OverlayRegion = Region.extend({
    show: function(view, options) {
        var region = this;
        Region.prototype.show.apply(this, arguments);
        view.$el.css({display: 'block', left: $(window).width(), width: $('#main').width()});
        var $backdrop = $('<div/>').addClass('overlay-backdrop');
        view.$el.parent().append($backdrop);
        // force window redraw or otherwise the backdrop animation won't work
        view.$el.parent().hide().show(0);
        // activate animations
        $backdrop.addClass('active');
        view.$el.animate({left: $(window).width() - $('#main').width()}, {
            duration: 400,
            complete: function() {
                view.$el.css('left', 'auto');
                view.$el.find(':input[autofocus]').focus();
            }
        });
        // callback: adjust overlay size on viewport changes
        $(window).on('resize', function() {
            view.$el.css('left', 'auto');
            view.$el.css('width', $('#main').width());
        });
        // callback: close overlay when navigating away from the current page
        this.listenTo(Backbone.history, 'route', function(router, route, params) {
            var currentFragment = router.current().fragment;
            if(currentFragment !== this.foregroundFragment) region.closeOverlay(false);
        });
        // callback: close overlay when requested by its view
        this.listenTo(view, 'view:close', function() {
            region.closeOverlay(true);
        });
        // callback: close overlay when pressing ESC
        $(document).on('keyup', function(e) {
            if(e.key === "Escape") region.closeOverlay(true);
        });
    },
    empty: function(callback) {
        var region = this,
            view = region.currentView;
        $(document).off('keyup');
        if(view) {
            var $backdrop = view.$el.parent().find('div.overlay-backdrop');
            view.$el.css({left: $(window).width() - $('#main').width()});
            view.$el.animate({left: $(window).width()}, {
                duration: 400,
                complete: function() {
                    Region.prototype.empty.apply(region, arguments);
                    if(callback) callback();
                }
            });
            $backdrop.on('transitionend', function() {
                $backdrop.remove();
            }).removeClass('active');
            // remove callbacks
            region.stopListening();
            $(window).off('resize');
        }
    },
    showOverlay: function(view, fragment, fallbackFragment, router, showOptions) {
        var region = this;
        if(router.current().fragment === fragment && !region.hasOwnProperty('backgroundFragment')) {
            // If the user loads the app from a URI that immediately shows an overlay,
            // we can't store that same URI as background fragment. In such a case,
            // Use the explicitly  provided fallback fragment as background fragment.
            region.backgroundFragment = fallbackFragment;
        } else region.backgroundFragment = router.current().fragment;
        region.backgroundRouter = router;
        if(region.hasOwnProperty('foregroundFragment') && region.foregroundFragment !== null) {
            // An overlay is already shown, close it first
            region.closeOverlay(false, function() {
                region.foregroundFragment = fragment;
                region.show(view, showOptions);
                router.navigate(fragment);
            });
        } else {
            region.foregroundFragment = fragment;
            region.show(view, showOptions);
            router.navigate(fragment);
        }
    },
    closeOverlay: function(updateRoutingState, callback) {
        if(updateRoutingState) this.backgroundRouter.navigate(this.backgroundFragment);
        this.backgroundFragment = null;
        this.foregroundFragment = null;
        this.empty(callback);
    }
});

export default Regions;
