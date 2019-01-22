define(['app/app',
        'tpl!app/modules/services/templates/ServiceUpload.tpl',
        'app/views/common',
        'jquery.fileupload'],
function(HoneySens, ServiceUploadTpl) {
    HoneySens.module('Services.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ServiceUpload = Marionette.ItemView.extend({
            template: ServiceUploadTpl,
            className: 'container-fluid',
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                }
            },
            onRender: function() {
                var view = this,
                    spinner = HoneySens.Views.inlineSpinner.spin();
                view.$el.find('div.progress, span.progress-text, div.serviceVerify, div.alert').hide();
                view.$el.find('div.loadingInline').html(spinner.el);
                view.$el.find('#serviceUpload').fileupload({
                    url: 'api/services',
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
                        if(parseInt(data.loaded / data.total * 100) >= 97) {
                            view.$el.find('div.serviceVerify').show();
                        }
                    },
                    fail: function(e, data) {
                        var errorMsg = JSON.parse(data.jqXHR.responseText);
                        view.$el.find('span.imageValidating').hide();
                        view.$el.find('div.alert-' + errorMsg.code).show();
                    },
                    done: function(e, data) {
                        view.$el.find('span.imageValidating').hide();
                        view.$el.find('div.alert-success').show();
                    }
                });
            }
        });
    });

    return HoneySens.Services.Views.ServiceUpload;
});