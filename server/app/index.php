<?php

require_once dirname(__FILE__) . '/../app/Bootstrap.php';

initClassLoading();
$config = initConfig();
$app = initSlim($config);
$messages = array();
$em = initDoctrine($config);
initDBSchema($messages, $em);
initDBEventManager($em);
$services = initServiceManager($config);
initRoutes($app, $em, $services, $config, $messages);
initSession();
$app->run();
