define(['app/app',
        'app/models',
        'tpl!app/common/templates/FileUpload.tpl',
        'app/views/common',
        'fileinput'],
function(HoneySens, Models, FileUploadTpl) {
    HoneySens.module('Common.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {

        function generateToken() {
            return Math.random().toString(36).substring(2);
        }

        Views.FileUpload = Marionette.ItemView.extend({
            template: FileUploadTpl,
            className: 'container-fluid',
            uploadToken: generateToken(),
            events: {
                'click button.createService': function() {
                    var service = new Models.Service(),
                        view = this;
                    view.$el.find('button.createService').addClass('hide');
                    service.save({task: this.model.id}, {
                        wait: true,
                        success: function(m) {
                            view.$el.find('div.serviceMgrRunning').removeClass('hide');
                            // The "create service" endpoint returns a task model
                            m.urlRoot = 'api/tasks';
                            HoneySens.Views.waitForTask(m, {
                                done: function() {
                                    view.$el.find('div.serviceMgrRunning').addClass('hide');
                                    view.$el.find('div.serviceMgrSuccess').removeClass('hide');
                                    // Switch to the new task model (because the remove button depends on it)
                                    view.model = m;
                                    view.$el.find('button.removeTask').removeClass('hide');
                                },
                                error: function() {
                                    view.$el.find('div.serviceMgrRunning').addClass('hide');
                                    view.$el.find('div.serviceMgrError').removeClass('hide');
                                    // Switch to the new task model (because the remove button depends on it)
                                    view.model = m;
                                    view.$el.find('button.removeTask').removeClass('hide');
                                }
                            });
                        },
                        error: function(m, xhr) {
                            view.$el.find('div.serviceMgrError').removeClass('hide');
                            view.$el.find('button.removeTask').removeClass('hide');
                            try {var code = JSON.parse(xhr.responseText).code}
                            catch(e) {code = 0}
                            var reason = 'Serverfehler';
                            switch(code) {
                                case 1: reason = 'Service-Registry nicht erreichbar'; break;
                                case 2: reason = 'Service ist bereits registriert'; break;
                            }
                            view.$el.find('div.serviceMgrError span.reason').text('(' + reason + ')');
                        }
                    });
                },
                'click button.createFirmware': function() {
                    var view = this;
                    view.$el.find('button.createFirmware').addClass('hide');
                    $.ajax({
                        type: 'POST',
                        url: 'api/platforms/firmware',
                        data: JSON.stringify({task: this.model.id}),
                        contentType: 'application/json',
                        success: function() {
                            // Clear model
                            view.model = null;
                            view.$el.find('div.firmwareSuccess').removeClass('hide');
                            view.$el.find('button.removeTask').removeClass('hide');
                        },
                        error: function(xhr) {
                            view.$el.find('div.firmwareError').removeClass('hide');
                            view.$el.find('button.removeTask').removeClass('hide');
                            try {var code = JSON.parse(xhr.responseText).code}
                            catch(e) {code = 0}
                            var reason = 'Serverfehler';
                            switch(code) {
                                case 1: reason = 'Unbekannte Plattform'; break;
                                case 2: reason = 'Firmware ist bereits registriert'; break;
                            }
                            view.$el.find('div.firmwareError span.reason').text('(' + reason + ')');
                        }
                    })
                },
                'click button.removeTask': function() {
                    if(this.model == null) HoneySens.request('view:content').overlay.empty();
                    else this.model.destroy({
                            wait: true, success: function () {
                                HoneySens.request('view:content').overlay.empty();
                            }
                        });
                },
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                }
            },
            onRender: function() {
                var view = this,
                    spinner = HoneySens.Views.inlineSpinner.spin();
                //view.$el.find('div.progress, span.progress-text').hide();
                view.$el.find('div.loadingInline').html(spinner.el);
                view.$el.find('#fileUpload').fileinput({
                    'autoReplace': true,
                    'dropZoneEnabled': false,
                    'enableResumableUpload': true,
                    'maxFileCount': 1,
                    'showPreview': false,
                    'showRemove': false,
                    'uploadUrl': 'api/tasks/upload',
                    'uploadExtraData': function() {
                        return {
                            'token': view.uploadToken
                        }
                    },
                    'resumableUploadOptions': {
                        'chunkSize': 50000
                    }
                }).on('filechunksuccess', function(ev, p, i, r, f, rm, data) {
                    if(!data.response.hasOwnProperty("task")) return;
                    // Successful upload: update local model, refresh view
                    var task = HoneySens.data.models.tasks.add(new Models.Task(data.response.task));
                    view.undelegateEvents();
                    view.model = task;
                    view.delegateEvents();
                    view.render();
                    HoneySens.Views.waitForTask(task);
                }).on('filechunkajaxerror', function(ev, p, i, r, f, rm, data) {
                    var errorMsg = data.jqXHR.hasOwnProperty('responseJSON') ? ' (' + data.jqXHR.responseJSON.error + ')' : '';
                    view.$el.find('div.uploadInvalid span.errorMsg').text('Auf dem Server ist ein Fehler aufgetreten' + errorMsg);
                    view.$el.find('div.uploadInvalid').removeClass('hide').siblings().addClass('hide');
                    // Generate a new unique token after upload failures
                    view.uploadToken = generateToken();
                }).on('fileuploaded', function(ev) {
                    // Generate a new unique token after successful uploads
                    view.uploadToken = generateToken();
                });
            },
            onDestroy: function() {
                var $fu = this.$el.find('#fileUpload');
                $fu.off('fileuploaded');
                $fu.off('fileuploaderror');
            },
            modelEvents: {
                change: 'render'
            },
            templateHelpers: {
                hasTask: function() {
                    return this.hasOwnProperty('id');
                },
                isServiceArchive: function() {
                    return this.result.type === 0;
                },
                isPlatformArchive: function() {
                    return this.result.type === 1;
                }
            }
        });
    });

    return HoneySens.Common.Views.FileUpload;
});
