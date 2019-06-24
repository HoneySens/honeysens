define(['app/app',
        'tpl!app/modules/tasks/templates/ModalAwaitTask.tpl',
        'app/views/common'],
function(HoneySens, ModalAwaitTaskTpl) {
    HoneySens.module('Tasks.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.ModalAwaitTask = Marionette.ItemView.extend({
            template: ModalAwaitTaskTpl,
            onRender: function() {
                var spinner = HoneySens.Views.inlineSpinner.spin();
                this.$el.find('div.loadingInline').html(spinner.el);
            },
            modelEvents: {
                change: 'render'
            }
        });
    });

    return HoneySens.Tasks.Views.ModalAwaitTask;
});