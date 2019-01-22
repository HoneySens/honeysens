define(['app/app',
        'tpl!app/modules/setup/templates/Division.tpl'],
function(HoneySens, DivisionTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.Division = Marionette.ItemView.extend({
            template: DivisionTpl,
            events: {
                'click button:submit': function(e) {
                    e.preventDefault();
                    this.$el.find('form').bootstrapValidator('validate');
                }
            },
            onRender: function() {
                var view = this;
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        divisionName: {
                            validators: {
                                notEmpty: {},
                                regexp: {
                                    regexp: /^[a-zA-Z0-9 ]+$/,
                                    message: 'Nur Groß-, Kleinbuchstaben und Zahlen erlaubt'
                                },
                                stringLength: {
                                    min: 1,
                                    max: 255
                                }
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var divisionName = view.$el.find('input[name="divisionName"]').val();
                    view.model.set({divisionName: divisionName});
                    HoneySens.request('setup:install:show', {step: 4, model: view.model});
                });
            }
        });
    });

    return HoneySens.Setup.Views.Division;
});