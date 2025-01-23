import HoneySens from 'app/app';
import $ from 'jquery';
import 'app/controller';
import 'app/patches/apply';
import 'app/modules/dashboard/module';
import 'app/modules/accounts/module';
import 'app/modules/sensors/module';
import 'app/modules/services/module';
import 'app/modules/platforms/module';
import 'app/modules/settings/module';
import 'app/modules/events/module';
import 'app/modules/info/module';
import 'app/modules/tasks/module';
import 'app/modules/logs/module';
import 'app/modules/setup/module';
import 'assets/css/honeysens.css';

$(document).ready(function() {
    HoneySens.start();
});