<?php
require_once dirname(__FILE__) . '/../app/Bootstrap.php';

initClassLoading();
$config = initConfig();
initSlim($config);
$em = initDoctrine($config);

$classLoader = new \Doctrine\Common\ClassLoader('Symfony', APPLICATION_PATH . '/lib/Doctrine');
$classLoader->register();

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);
