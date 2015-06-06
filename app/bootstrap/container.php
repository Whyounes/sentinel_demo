<?php

//register TWig
$app->container->twigLoader = new Twig_Loader_Filesystem(__DIR__.'/../views');
$app->container->twig = new Twig_Environment($app->container->twigLoader, array(
    'cache' => false, //__DIR__.'/../cache',
));

// Register Eloquent configuration
$capsule = new \Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'capsule',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
]);
$capsule->bootEloquent();

$app->container->sentry = \Cartalyst\Sentry\Facades\Native\Sentry::createSentry();
