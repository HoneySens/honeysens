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
                view.$el.find('div.progress, span.progress-text, div.alert, span.imageValidating').hide();
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
                            view.$el.find('span.imageValidating').show();
                        }
                    },
                    fail: function(e, data) {
                        var errorMsg ='Es ist ein Fehler aufgetreten';
                        try {
                            switch (JSON.parse(data.jqXHR.responseText).code) {
                                case 1:
                                    errorMsg = 'Das hochgeladene Service-Archiv besitzt kein gültiges Format!';
                                    break;
                                case 2:
                                    errorMsg = 'Das Service-Archiv beinhaltet keine gültigen Metadaten!';
                                    break;
                                case 3:
                                    errorMsg = 'Service wurde erfolgreich registriert!';
                                    break;
                            }
                        } catch(e) {
                            if (e instanceof SyntaxError) errorMsg = 'Service-Archiv ungültig';
                        }
                        view.$el.find('div.imageInvalid span.errorMsg').text(errorMsg);
                        view.$el.find('div.imageInvalid').show().siblings().hide();
                    },
                    done: function(e, data) {
                        view.$el.find('span.archiveName').text(data.files[0].name);
                        view.$el.find('div.alert-success').show().siblings().hide();
                    }
                });
            }
        });
    });

    return HoneySens.Services.Views.ServiceUpload;
});
