/*
  Backgrid select-filter extension
  http://github.com/amiliaapp/backgrid-select-filter

  Copyright (c) 2014 Amilia Inc.
  Written by Martin Drapeau
  Licensed under the MIT @license
 */
(function(){
  var SelectFilter = Backgrid.Extension.SelectFilter = Backbone.View.extend({
    tagName: "select",
    className: "backgrid-filter",
    template: _.template([
      "<% for (var i=0; i < options.length; i++) { %>",
      "  <option value='<%=JSON.stringify(options[i].value)%>' <%=options[i].value === initialValue ? 'selected=\"selected\"' : ''%>><%=options[i].label%></option>",
      "<% } %>"
    ].join("\n")),
    events: {
      "change": "onChange"
    },
    defaults: {
      selectOptions: undefined,
      field: undefined,
      clearValue: null,
      initialValue: undefined,
      makeMatcher: function(value) {
        return function(model) {
          return model.get(this.field) == value;
        };
      }
    },
    initialize: function(options) {
      SelectFilter.__super__.initialize.apply(this, arguments);

      _.defaults(this, options || {}, this.defaults);
      if (_.isEmpty(this.selectOptions) || !_.isArray(this.selectOptions)) throw "Invalid or missing selectOptions.";
      if (_.isEmpty(this.field) || !this.field.length) throw "Invalid or missing field.";
      if (this.initialValue === undefined) this.initialValue = this.clearValue;
      if (((Backbone.PageableCollection) && (this.collection instanceof Backbone.PageableCollection) && (this.collection.mode !== "client"))) this.serverSide = true; // Looking for a pageableCollection in "server" or "infinite" mode

      var collection = this.collection = this.collection.fullCollection || this.collection;

      if (!this.serverSide) {
        var shadowCollection = this.shadowCollection = collection.clone();

        this.listenTo(collection, "add", function (model, collection, options) {
          shadowCollection.add(model, options);
        });
        this.listenTo(collection, "remove", function (model, collection, options) {
          shadowCollection.remove(model, options);
        });
        this.listenTo(collection, "sort", function (col) {
          if (this.currentValue() == this.clearValue) shadowCollection.reset(col.models);
        });
        this.listenTo(collection, "reset", function (col, options) {
          options = _.extend({reindex: true}, options || {});
          if (options.reindex && options.from == null && options.to == null) {
            shadowCollection.reset(col.models);
          }
        });
      } else { // Add the initial value to the PageableCollection's queryParams object if in server side mode
        if (this.initialValue !== this.clearValue) this.collection.queryParams[this.field] = this.initialValue;
      }
    },
    render: function() {
      this.$el.empty().append(this.template({
        options: this.selectOptions,
        initialValue: this.initialValue
      }));
      if (!this.serverSide) this.onChange();
      return this;
    },
    currentValue: function() {
      return JSON.parse(this.$el.val());
    },
    onChange: function(e) {
      var col = this.collection,
        field = this.field,
        value = this.currentValue(),
        matcher = _.bind(this.makeMatcher(value), this);

      if (this.serverSide) {
        if (value !== this.clearValue) {
          if (Object.prototype.toString.call(value) === '[object Array]') {
            value = value.join(","); // Send the query parameter as an array of concatenated strings if needed
          }
          col.queryParams[field] = value;
        } else { // Don't send the query parameter if null (or this.clearValue)
          delete col.queryParams[field];
        }
        col.getFirstPage();
      } else {
        if (col instanceof Backbone.PageableCollection)
          col.getFirstPage({silent: true});

        if (value !== this.clearValue)
          col.reset(this.shadowCollection.filter(matcher), {reindex: false});
        else
          col.reset(this.shadowCollection.models, {reindex: false});
      }
    }
  });
}).call(this);
