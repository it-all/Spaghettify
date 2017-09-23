<?php
declare(strict_types=1);

// GLOBAL CONSTANTS
define('APP_ROOT', __DIR__ . '/' );
define('VENDOR_DIR', APP_ROOT . '../vendor');

require VENDOR_DIR . '/autoload.php';

$env = require APP_ROOT . '/../config/env.php';

$spag = new \It_All\Spaghettify\Src\Spaghettify($env);
$spag->run();
