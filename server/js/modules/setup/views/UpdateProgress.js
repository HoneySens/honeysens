define(['app/app',
        'tpl!app/modules/setup/templates/UpdateProgress.tpl',
        'app/views/common'],
function(HoneySens, UpdateProgressTpl) {
    HoneySens.module('Setup.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        function launchTimer(view) {
            if(!view.timerActive) return;
            setTimeout(function() {
                $.ajax({
                    type: 'GET',
                    url: 'api/system/update',
                    success: function() {
                        view.$el.find('div.busy').addClass('hidden');
                        view.$el.find('div.success').removeClass('hidden');
                        view.timerActive = false;
                    },
                    error: launchTimer(view)
                })
            }, 2000);
        }

        Views.UpdateProgress = Marionette.ItemView.extend({
            template: UpdateProgressTpl,
            timerActive: false,
            events: {
                'click button': function(e) {
                    e.preventDefault();
                    HoneySens.execute('logout');
                }
            },
            onRender: function() {
                var spinner = HoneySens.Views.spinner.spin();
                this.$el.find('div.loading').html(spinner.el);
                this.timerActive = true;
                launchTimer(this);
            }
        });
    });

    return HoneySens.Setup.Views.UpdateProgress;
});