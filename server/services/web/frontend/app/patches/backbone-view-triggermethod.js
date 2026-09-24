// Marionette's Region.show()/empty() and some internal helpers call
// `view.triggerMethod(...)` on the shown view, assuming it has that method.
// Marionette views do, but a plain Backbone.View does not.
// Backgrid introduces several components based on Backbone.View
// that do not use Marionette lifecycle hooks (no onRender / onDomRefresh / onAttach, etc.),
// so a no-op triggerMethod on Backbone.View.prototype is added as interoperability shim.
//
// This is also safe for Marionette views: They define their own triggerMethod,
// which shadows this one, so only plain Backbone views pick it up.
import Backbone from 'backbone';

if (Backbone.View.prototype.triggerMethod === undefined) {
    Backbone.View.prototype.triggerMethod = function() {};
}
