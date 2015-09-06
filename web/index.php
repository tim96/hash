<?php

// Calculate execution time
// $time_start = microtime(true);

ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../app/app.php';
require __DIR__.'/../app/config/config_prod.php';

$app->run();

// $execution_time = (microtime(true) - $time_start);
// echo '<b>Total Execution Time:</b> '.$execution_time.' Seconds';