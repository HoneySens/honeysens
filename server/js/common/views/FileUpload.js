define(['app/app',
        'app/models',
        'tpl!app/common/templates/FileUpload.tpl',
        'app/views/common',
        'jquery.fileupload'],
function(HoneySens, Models, FileUploadTpl) {
    HoneySens.module('Common.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.FileUpload = Marionette.ItemView.extend({
            template: FileUploadTpl,
            className: 'container-fluid',
            events: {
                'click button.createService': function() {
                    var service = new Models.Service(),
                        view = this;
                    view.$el.find('button.createService').addClass('hide');
                    service.save({task: this.model.id}, {wait: true,
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
                view.$el.find('div.progress, span.progress-text').hide();
                view.$el.find('div.loadingInline').html(spinner.el);
                view.$el.find('#fileUpload').fileupload({
                    url: 'api/tasks/upload',
                    dataType: 'json',
                    maxChunkSize: 50000000, // TODO define globally
                    start: function() {
                        // TODO use an add callback instead of this to allow saving of the XHR object and allow abortion of the upload task
                        view.$el.find('span.fileinput-button').hide().siblings('div.progress').show();
                        view.$el.find('span.progress-text').show();
                    },
                    progressall: function(e, data) {
                        var progress = parseInt(data.loaded / data.total * 100) + '%';
                        view.$el.find('div.progress-bar').css('width', progress).text(progress);
                        view.$el.find('span.progress-loaded').text((data.loaded / (1000 * 1000)).toFixed(1));
                        view.$el.find('span.progress-total').text(+(data.total / (1000 * 1000)).toFixed(1));
                        //if(parseInt(data.loaded / data.total * 100) >= 97) {
                            //view.$el.find('span.uploadValidating').show();
                        //}
                    },
                    fail: function(e, data) {
                        var errorMsg ='Es ist ein Fehler aufgetreten';
                        view.$el.find('div.uploadInvalid span.errorMsg').text(errorMsg);
                        view.$el.find('div.uploadInvalid').show().siblings().hide();
                    },
                    done: function(e, data) {
                        // Update local model, refresh view
                        var task = HoneySens.data.models.tasks.add(new Models.Task(data.result.task));
                        view.undelegateEvents();
                        view.model = task;
                        view.delegateEvents();
                        view.render();
                        HoneySens.Views.waitForTask(task);
                    }
                });
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