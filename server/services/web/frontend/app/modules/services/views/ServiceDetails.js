import HoneySens from 'app/app';
import Backgrid from 'backgrid';
import ServiceDetailsTpl from 'app/modules/services/templates/ServiceDetails.tpl';
import RevisionListActionsCellTpl from 'app/modules/services/templates/RevisionListActionsCell.tpl';
import VersionListActionsCellTpl from 'app/modules/services/templates/VersionListActionsCell.tpl';
import RevisionListStatusCellTpl from 'app/modules/services/templates/RevisionListStatusCell.tpl';
import 'backgrid-subgrid-cell';
import 'app/views/common';

HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ServiceDetails = Marionette.LayoutView.extend({
        template: _.template(ServiceDetailsTpl),
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
                    label: _.t('services:serviceRevision'),
                    editable: false,
                    cell: 'string'
                }, {
                    name: 'architecture',
                    label: _.t('services:serviceArchitectures'),
                    editable: false,
                    sortable: false,
                    cell: 'string'
                }, {
                    name: 'status',
                    label: _.t('services:serviceStatus'),
                    editable: false,
                    cell: Backgrid.Cell.extend({
                        template: _.template(RevisionListStatusCellTpl),
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
                    label: _.t("actions"),
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: _.template(RevisionListActionsCellTpl),
                        events: {
                            'click button.removeRevision': function(e) {
                                e.preventDefault();
                                HoneySens.request('services:revisions:remove', this.model);
                            }
                        },
                        render: function() {
                            // Hide action buttons for all default service revisions
                            this.model.set('nondef', this.model.get('revision') !== view.model.get('default_revision'));
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
                    label: _.t('services:serviceRevision'),
                    editable: false,
                    cell: 'string'
                }, {
                    name: 'architectures',
                    label: _.t('services:serviceArchitectures'),
                    editable: false,
                    sortable: false,
                    cell: 'string'
                }, {
                    name: 'status',
                    label: _.t('services:serviceStatus'),
                    editable: false,
                    cell: Backgrid.Cell.extend({
                        template: _.template(RevisionListStatusCellTpl),
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
                    label: _.t('actions'),
                    editable: false,
                    sortable: false,
                    cell: Backgrid.Cell.extend({
                        template: _.template(VersionListActionsCellTpl),
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

export default HoneySens.Services.Views.ServiceDetails;