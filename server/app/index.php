<?php

require_once dirname(__FILE__) . '/../app/Bootstrap.php';

initClassLoading();
$config = initConfig();
$app = initSlim($config);
$em = initDoctrine();
initDBSchema($em);
initDBEventManager($em);
$services = initServiceManager($config, $em);
initRoutes($app, $em, $services, $config);
$app->run();
