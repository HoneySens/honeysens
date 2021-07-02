<?php

require_once dirname(__FILE__) . '/../app/Bootstrap.php';

initClassLoading();
$config = initConfig();
$app = initSlim($config);
$messages = array();
$em = initDoctrine();
initDBSchema($messages, $em);
initDBEventManager($em);
$services = initServiceManager($config, $em);
initRoutes($app, $em, $services, $config, $messages);
initSession($app);
$app->run();
