define(['app/app',
        'app/models',
        'tpl!app/modules/settings/templates/ModalSendTestMail.tpl',
        'app/views/common',
        'validator'],
function(HoneySens, Models, ModalSendTestMailTpl) {
    HoneySens.module('Settings.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        // The model this view receives is a vanilla Backbone.Model() upon first invocation,
        // but will be exchanged with a Task model when the 'send' button is pressed.
        Views.ModalSendTestMail = Marionette.ItemView.extend({
            template: ModalSendTestMailTpl,
            events: {
                'click button.btn-primary': function(e) {
                    e.preventDefault();
                    this.$el.find('form').trigger('submit');
                },
                'click button.btn-default': function(e) {
                    e.preventDefault();
                    if(this.model.has('status')) {
                        this.model.destroy({
                            wait: true, success: function() {
                                HoneySens.request('view:modal').empty();
                            }
                        })
                    } else HoneySens.request('view:modal').empty();
                }
            },
            onRender: function() {
                var view = this;

                // Focus input field when the dialog is shown
                $('#modal').on('shown.bs.modal', function(){
                    view.$el.find('input[name="recipient"]').focus();
                });

                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
                        e.preventDefault();
                        // Show pending spinner, hide other controls
                        view.$el.find('input[name="recipient"]').prop('disabled', true);
                        var spinner = HoneySens.Views.inlineSpinner.spin();
                        view.$el.find('div.loadingInline').html(spinner.el);
                        view.$el.find('div.sendPending').removeClass('hidden');
                        view.$el.find('button.btn-primary').addClass('hidden');
                        // Update model and send request
                        view.model.set('recipient', view.$el.find('input[name="recipient"]').val());
                        $.ajax({
                            type: 'POST',
                            url: 'api/settings/testmail',
                            dataType: 'json',
                            data: JSON.stringify(view.model),
                            success: function(resp, code, xhr) {
                                HoneySens.Views.waitForTask(new Models.Task(xhr.responseJSON), {
                                    done: function(task) {
                                        view.model = task;
                                        view.render();
                                    },
                                    error: function(task) {
                                        view.model = task;
                                        view.render();
                                    }
                                })
                            }
                        });
                    }
                });
            },
            templateHelpers: {
                getError: function() {
                    return this.result.error;
                },
                getRecipient: function() {
                    if(this.hasOwnProperty('params') && this.params.hasOwnProperty('to')) return this.params.to;
                },
                isDone: function() {
                    return this.hasOwnProperty('status') && (this.status === 2 || this.status === 3);
                },
                isError: function() {
                    // If there was an error, 'result' would be an object with an 'error' property
                    return this.hasOwnProperty('result') && this.result != null;
                }
            },
            onDestroy: function() {
                $('#modal').off('shown.bs.modal');
            }
        });
    });

    return HoneySens.Settings.Views.ModalSendTestMail;
});