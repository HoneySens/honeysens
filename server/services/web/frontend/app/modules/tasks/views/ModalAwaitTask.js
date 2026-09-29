import i18n from 'app/common/i18n';
import { TaskStatus } from 'app/models';
import { View } from 'backbone.marionette';
import ModalAwaitTaskTpl from 'app/modules/tasks/templates/ModalAwaitTask.tpl';
import { inlineSpinner } from 'app/views/common';

const ModalAwaitTask = View.extend({
    template: _.template(ModalAwaitTaskTpl),
    templateContext: {
        ...i18n,
        TaskStatus: TaskStatus
    },
    onRender: function() {
        var spinner = inlineSpinner.spin();
        this.$el.find('div.loadingInline').html(spinner.el);
    },
    modelEvents: {
        change: 'render'
    }
});

export default ModalAwaitTask;
