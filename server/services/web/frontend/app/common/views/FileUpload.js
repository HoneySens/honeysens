import i18n from 'app/common/i18n';
import { View } from 'backbone.marionette';
import { radio } from 'app/radio';
import HoneySens from 'app/app';
import { Service, Task, TaskStatus } from 'app/models';
import FileUploadTpl from 'app/common/templates/FileUpload.tpl';
import { waitForTask, inlineSpinner } from 'app/views/common';
import 'bootstrap-fileinput';

function generateToken() {
    return Math.random().toString(36).substring(2);
}

const FileUpload = View.extend({
    template: _.template(FileUploadTpl),
    className: 'container-fluid',
    uploadToken: generateToken(),
    events: {
        'click button.createService': function() {
            var service = new Service(),
                view = this;
            view.$el.find('button.createService').addClass('hide');
            service.save({task: this.model.id}, {
                wait: true,
                success: function(m) {
                    view.$el.find('div.serviceMgrRunning').removeClass('hide');
                    // The "create service" endpoint returns a task model
                    m.urlRoot = 'api/tasks';
                    waitForTask(m, {
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
                    var reason = i18n.t('genericServerError');
                    switch(code) {
                        case 1: reason = i18n.t('uploadServiceErrorRegistryUnavailable'); break;
                        case 2: reason = i18n.t('uploadServiceErrorRegistryDuplicate'); break;
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
                    var reason = i18n.t('genericServerError');
                    switch(code) {
                        case 1: reason = i18n.t('uploadFirmwareErrorUnknownPlatform'); break;
                        case 2: reason = i18n.t('uploadFirmwareErrorDuplicate'); break;
                    }
                    view.$el.find('div.firmwareError span.reason').text('(' + reason + ')');
                }
            })
        },
        'click button.cancel': function() {
            if(this.model == null
                || this.model.get('status') === TaskStatus.SCHEDULED
                || this.model.get('status') === TaskStatus.RUNNING)
                radio.request('view:content').getRegion('overlay').empty();
            else this.model.destroy({
                wait: true, success: function () {
                    radio.request('view:content').getRegion('overlay').empty();
                }
            });
        }
    },
    onRender: function() {
        var view = this,
            spinner = inlineSpinner.spin();
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
            var task = HoneySens.data.models.tasks.add(new Task(data.response.task));
            view.model = task;
            view.render();
            waitForTask(task, {
                done: function() {
                    view.render();
                }
            });
        }).on('filechunkajaxerror', function(ev, p, i, r, f, rm, data) {
            var errorMsg = data.jqXHR.hasOwnProperty('responseJSON') ? ' (' + data.jqXHR.responseJSON.error + ')' : '';
            view.$el.find('div.uploadInvalid span.errorMsg').text(i18n.t('genericServerError') + errorMsg);
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
    templateContext: {
        ...i18n,
        hasTask: function() {
            return this.hasOwnProperty('id');
        },
        isServiceArchive: function() {
            return this.result.type === 0;
        },
        isPlatformArchive: function() {
            return this.result.type === 1;
        },
        TaskStatus: TaskStatus
    }
});

export default FileUpload;
