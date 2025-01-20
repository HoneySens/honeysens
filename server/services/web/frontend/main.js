require(['app/app', 'jquery', 'app/controller',
    'app/patches/apply',
    'app/modules/dashboard/module',
    'app/modules/accounts/module',
    'app/modules/sensors/module',
    'app/modules/services/module',
    'app/modules/platforms/module',
    'app/modules/settings/module',
    'app/modules/events/module',
    'app/modules/info/module',
    'app/modules/tasks/module',
    'app/modules/logs/module',
    'app/modules/setup/module',
    'assets/css/honeysens.css'], function(HoneySens, $) {
        $(document).ready(function() {
            HoneySens.start();
        });
});
