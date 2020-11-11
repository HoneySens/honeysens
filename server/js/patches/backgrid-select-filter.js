define(['backgrid-select-filter'],
function() {
    var prototype = Backgrid.Extension.SelectFilter.prototype;

    // Restore original collection when the filter isn't shown anymore, preventing permanent modification of the
    // upstream collection
    prototype.remove = function() {
        if(!this.serverSide) this.collection.reset(this.shadowCollection.models, {reindex: false});
        Backbone.View.prototype.remove.apply(this, arguments);
    }

    // Upon upstream collection reset (such as periodic model updates), re-evaluate our filter condition.
    // We should override initialize(), but that function is too long. That's why we inject our listener on render().
    prototype.render = function() {
        this.listenTo(this.shadowCollection, 'reset', function() {
            this.onChange();
        });
        // Upstream function below
        this.$el.empty().append(this.template({
            options: this.selectOptions,
            initialValue: this.initialValue
        }));
        if (!this.serverSide) this.onChange();
        return this;
    }
});