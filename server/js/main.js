requirejs.config({
    baseUrl: 'js/lib',
    paths: {
        marionette: 'backbone.marionette',
        json: 'json2',
        datatables: 'dtables/jquery.dataTables',
        'datatables-bootstrap': 'dtables/dataTables.bootstrap',
        chart: 'Chart.bundle',
        tpl: 'underscore-tpl',
        app: '..'
    },
    shim: {
        marionette: {exports: 'Backbone.Marionette', deps: ['backbone']},
        json: {exports: 'JSON'},
        bootstrap: {exports: '$', deps: ['jquery']},
        'backgrid-select-filter': {exports: 'Backgrid.Extension.SelectFilter', deps: ['backgrid']},
        'backgrid-subgrid-cell': {deps: ['backgrid']}
    },
    urlArgs: 'bust=' + (new Date()).getTime()
});

require(['app/app', 'jquery', 'json', 'app/controller',
    'app/modules/dashboard/module',
    'app/modules/accounts/module',
    'app/modules/sensors/module',
    'app/modules/services/module',
    'app/modules/platforms/module',
    'app/modules/settings/module',
    'app/modules/events/module',
    'app/modules/info/module',
    'app/modules/setup/module'], function(HoneySens, $) {
        $(document).ready(function() {
            HoneySens.start();
        });
});
