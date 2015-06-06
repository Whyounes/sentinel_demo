<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new \Slim\Slim();

//register bindings
include_once __DIR__.'/../app/bootstrap/container.php';

include_once __DIR__.'/../app/routes.php';

$app->run();
