define(['app/app',
        'tpl!app/modules/platforms/templates/FirmwareUpload.tpl',
        'app/views/common',
        'jquery.fileupload'],
function(HoneySens, FirmwareUploadTpl) {
    HoneySens.module('Platforms.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.FirmwareUpload = Marionette.ItemView.extend({
            template: FirmwareUploadTpl,
            className: 'container-fluid',
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                }
            },
            onRender: function() {
                var view = this,
                    spinner = HoneySens.Views.inlineSpinner.spin();
                view.$el.find('#imageInfo, div.progress, span.progress-text, div.imageVerify span:not(.glyphicon,.errorMsg), div.imageVerify div.alert').hide();
                view.$el.find('div.loadingInline').html(spinner.el);
                view.$el.find('#imageUpload').fileupload({
                    url: 'api/platforms/firmware',
                    dataType: 'json',
                    maxChunkSize: 50000000,
                    start: function () {
                        // TODO use an add callback instead of this to allow saving of the XHR object and allow abortion of the upload task
                        view.$el.find('span.fileinput-button').hide().siblings('div.progress').show();
                        view.$el.find('span.progress-text').show();
                    },
                    progressall: function(e, data) {
                        var progress = parseInt(data.loaded / data.total * 100) + '%';
                        view.$el.find('div.progress-bar').css('width', progress).text(progress);
                        view.$el.find('span.progress-loaded').text((data.loaded / (1000 * 1000)).toFixed(1));
                        view.$el.find('span.progress-total').text(+(data.total / (1000 * 1000)).toFixed(1));
                        if (parseInt(data.loaded / data.total * 100) >= 97) {
                            view.$el.find('div.imageVerify span.imageValidating').show();
                        }
                    },
                    fail: function(e, data) {
                        var errorMsg = 'Es ist ein Fehler aufgetreten';
                        try {
                            switch (JSON.parse(data.jqXHR.responseText).code) {
                                case 1:
                                    errorMsg = 'Firmware ungültig';
                                    break;
                                case 2:
                                    errorMsg = 'Firmware enthält fehlerhafte Metadaten';
                                    break;
                                case 3:
                                    errorMsg = 'Firmware ist bereits vorhanden';
                                    break;
                            }
                        } catch(e) {
                            if (e instanceof SyntaxError) errorMsg = 'Firmware ungültig'
                        }
                        view.$el.find('div.imageVerify span.errorMsg').text(errorMsg);
                        view.$el.find('div.imageVerify div.imageInvalid').show().siblings().hide();
                    },
                    done: function(e, data) {
                        var file = data.result.files[0];
                        view.$el.find('div.imageVerify div.imageValid').show().siblings().hide();
                        view.$el.find('p.imageName').text(_.escape(file.image.name));
                        view.$el.find('p.imageVersion').text(_.escape(file.image.version));
                        view.$el.find('p.imageDescription').text(_.escape(file.image.description));
                        view.$el.find('#imageInfo').show();
                        HoneySens.data.models.platforms.fetch();
                    }
                });
            }
        });
    });

    return HoneySens.Platforms.Views.FirmwareUpload;
});
