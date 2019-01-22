define(['app/app',
        'backgrid',
        'tpl!app/modules/services/templates/ServiceDetails.tpl',
        'tpl!app/modules/services/templates/RevisionListActionsCell.tpl',
        'tpl!app/modules/services/templates/VersionListActionsCell.tpl',
        'tpl!app/modules/services/templates/RevisionListStatusCell.tpl',
        'backgrid-subgrid-cell',
        'app/views/common'],
function(HoneySens, Backgrid, ServiceDetailsTpl, RevisionListActionsCellTpl, VersionListActionsCellTpl, RevisionListStatusCellTpl) {
    HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ServiceDetails = Marionette.LayoutView.extend({
            template: ServiceDetailsTpl,
            className: 'container-fluid',
            regions: {
                revisions: 'div.revisions'
            },
            revisionStatus: null,
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                }
            },
            onRender: function() {
                // Set subgrid collections
                var modelCollection = this.model.getVersions();
                modelCollection.forEach(function(m) {
                    m.set('subcollection', m.getRevisions());
                });

                var view = this,
                    subcolumns = [{
                        name: 'X',
                        label: '',
                        cell: 'string',
                        editable: false,
                        sortable: false
                    },{
                        name: 'revision',
                        label: 'Revision',
                        editable: false,
                        cell: 'string'
                    }, {
                        name: 'architecture',
                        label: 'Architekturen',
                        editable: false,
                        sortable: false,
                        cell: 'string'
                    }, {
                        name: 'status',
                        label: 'Status',
                        editable: false,
                        cell: Backgrid.Cell.extend({
                            template: RevisionListStatusCellTpl,
                            render: function() {
                                // Mix template helpers into template data
                                var templateData = this.model.attributes;
                                templateData.getStatus = function() {
                                    if(view.revisionStatus == null) return null;
                                    if(view.revisionStatus == false) return false;
                                    return view.revisionStatus[this.id];
                                };
                                // Color-code cell depending on the model status
                                switch(templateData.getStatus()) {
                                    case true:
                                        this.$el.addClass('success');
                                        break;
                                    case false:
                                        this.$el.addClass('danger');
                                        break;
                                    default:
                                        this.$el.addClass('info');
                                        break;
                                }
                                // Render template
                                this.$el.html(this.template(templateData));
                                return this;
                            }
                        })
                    }, {
                        label: 'Aktionen',
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            template: RevisionListActionsCellTpl,
                            events: {
                                'click button.removeRevision': function(e) {
                                    e.preventDefault();
                                    HoneySens.request('services:revisions:remove', this.model);
                                }
                            },
                            render: function() {
                                this.$el.html(this.template(this.model.attributes));
                                this.$el.find('button').tooltip();
                                this.delegateEvents(); // Not exactly sure why events won't work without this call
                                return this;
                            }
                        })
                    }],
                    columns = [{
                        name: 'subgrid',
                        label: '',
                        cell: 'subgrid',
                        editable: false,
                        sortable: false,
                        optionValues: subcolumns
                    }, {
                        name: 'id',
                        label: 'Revision',
                        editable: false,
                        cell: 'string'
                    }, {
                        name: 'architectures',
                        label: 'Architekturen',
                        editable: false,
                        sortable: false,
                        cell: 'string'
                    }, {
                        name: 'status',
                        label: 'Status',
                        editable: false,
                        cell: Backgrid.Cell.extend({
                            template: RevisionListStatusCellTpl,
                            render: function() {
                                // Mix template helpers into template data
                                var templateData = this.model.attributes;
                                // Status is dependent on the status of all revisions that belong to this version
                                if(view.revisionStatus == null) templateData.revisionStatus = null;
                                else {
                                    templateData.revisionStatus = true;
                                    this.model.get('revisions').forEach(function (r) {
                                        templateData.revisionStatus = templateData.revisionStatus && view.revisionStatus[r.id];
                                    });
                                }
                                templateData.getStatus = function() {
                                    if(view.revisionStatus == null) return null;
                                    if(view.revisionStatus == false) return false;
                                    return this.revisionStatus;
                                };
                                // Color-code cell depending on the model status
                                switch(templateData.getStatus()) {
                                    case true:
                                        this.$el.addClass('success');
                                        break;
                                    case false:
                                        this.$el.addClass('danger');
                                        break;
                                    default:
                                        this.$el.addClass('info');
                                        break;
                                }
                                // Render template
                                this.$el.html(this.template(templateData));
                                return this;
                            }
                        })
                    },{
                        label: 'Aktionen',
                        editable: false,
                        sortable: false,
                        cell: Backgrid.Cell.extend({
                            template: VersionListActionsCellTpl,
                            events: {
                                'click button.setDefaultRevision': function(e) {
                                    e.preventDefault();
                                    view.model.save({default_revision: this.model.id}, {
                                        wait: true,
                                        success: function() {
                                            // Force grid redraw
                                            modelCollection.trigger('reset');
                                        }
                                    });
                                }
                            },
                            render: function() {
                                // Hide action buttons for the default service revision
                                if(this.model.id !== view.model.get('default_revision')) {
                                    this.$el.html(this.template(this.model.attributes));
                                    this.$el.find('button').tooltip();
                                }
                                return this;
                            }
                        })
                    }];
                var row = Backgrid.Row.extend({
                    render: function() {
                        Backgrid.Row.prototype.render.call(this);
                        if(view.model.get('default_revision') == this.model.id) this.$el.addClass('warning');
                        return this;
                    }
                });

                var grid = new Backgrid.Grid({
                    row: row,
                    columns: columns,
                    collection: modelCollection,
                    className: 'table table-striped'
                });
                this.revisions.show(grid);
                grid.sort('id', 'descending');

                // Request registry status data for this service in the background
                $.ajax({
                    method: 'GET',
                    url: 'api/services/' + this.model.id + '/status',
                    success: function(data) {
                        view.revisionStatus = JSON.parse(data);
                        modelCollection.trigger('reset', modelCollection, {});
                    },
                    error: function(data) {
                        // Global flag to indicate the repository doesn't exist, is unreachable
                        // or there is some other server-side problem
                        view.revisionStatus = false;
                        modelCollection.trigger('reset', modelCollection, {});
                    }
                });
            }
        });
    });

    return HoneySens.Services.Views.ServiceDetails;
});