<?php

include_once(__DIR__ . '/custom_autoload.php');

use BimRunner\Application\RunnerApplication;

$app = new RunnerApplication('Generate runner', __DIR__, 'BimRunner', 'Generator/Actions');
$app->run();
