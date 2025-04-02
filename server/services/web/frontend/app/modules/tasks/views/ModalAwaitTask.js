import HoneySens from 'app/app';
import ModalAwaitTaskTpl from 'app/modules/tasks/templates/ModalAwaitTask.tpl';
import 'app/views/common';

HoneySens.module('Tasks.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.ModalAwaitTask = Marionette.ItemView.extend({
        template: _.template(ModalAwaitTaskTpl),
        onRender: function() {
            var spinner = HoneySens.Views.inlineSpinner.spin();
            this.$el.find('div.loadingInline').html(spinner.el);
        },
        modelEvents: {
            change: 'render'
        }
    });
});

export default HoneySens.Tasks.Views.ModalAwaitTask;