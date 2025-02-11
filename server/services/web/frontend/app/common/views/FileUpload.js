import HoneySens from 'app/app';
import Models from 'app/models';
import FileUploadTpl from 'app/common/templates/FileUpload.tpl';
import 'app/views/common';
import 'bootstrap-fileinput';

HoneySens.module('Common.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {

    function generateToken() {
        return Math.random().toString(36).substring(2);
    }

    Views.FileUpload = Marionette.ItemView.extend({
        template: _.template(FileUploadTpl),
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
                                // Switch to the new task model (because the cancel button depends on it)
                                view.model = m;
                            },
                            error: function() {
                                view.$el.find('div.serviceMgrRunning').addClass('hide');
                                view.$el.find('div.serviceMgrError').removeClass('hide');
                                // Switch to the new task model (because the cancel button depends on it)
                                view.model = m;
                            }
                        });
                    },
                    error: function(m, xhr) {
                        view.$el.find('div.serviceMgrError').removeClass('hide');
                        try {var code = JSON.parse(xhr.responseText).code}
                        catch(e) {code = 0}
                        var reason = _.t('genericServerError');
                        switch(code) {
                            case 1: reason = _.t('uploadServiceErrorRegistryUnavailable'); break;
                            case 2: reason = _.t('uploadServiceErrorRegistryDuplicate'); break;
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
                    },
                    error: function(xhr) {
                        view.$el.find('div.firmwareError').removeClass('hide');
                        try {var code = JSON.parse(xhr.responseText).code}
                        catch(e) {code = 0}
                        var reason = _.t('genericServerError');
                        switch(code) {
                            case 1: reason = _.t('uploadFirmwareErrorUnknownPlatform'); break;
                            case 2: reason = _.t('uploadFirmwareErrorDuplicate'); break;
                        }
                        view.$el.find('div.firmwareError span.reason').text('(' + reason + ')');
                    }
                })
            },
            'click button.cancel': function() {
                if(this.model == null
                    || this.model.get('status') === Models.Task.status.SCHEDULED
                    || this.model.get('status') === Models.Task.status.RUNNING)
                    HoneySens.request('view:content').overlay.empty();
                else this.model.destroy({
                    wait: true, success: function () {
                        HoneySens.request('view:content').overlay.empty();
                    }
                });
            }
        },
        onRender: function() {
            var view = this,
                spinner = HoneySens.Views.inlineSpinner.spin();
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
                if(!data.response.hasOwnProperty('task')) return;
                // Successful upload: update local model, refresh view
                var task = HoneySens.data.models.tasks.add(new Models.Task(data.response.task));
                view.undelegateEvents();
                view.model = task;
                view.delegateEvents();
                view.render();
                HoneySens.Views.waitForTask(task);
            }).on('filechunkajaxerror', function(ev, p, i, r, f, rm, data) {
                var errorMsg = data.jqXHR.hasOwnProperty('responseJSON') ? ' (' + data.jqXHR.responseJSON.error + ')' : '';
                view.$el.find('div.uploadInvalid span.errorMsg').text(_.t('genericServerError') + errorMsg);
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

export default HoneySens.Common.Views.FileUpload;