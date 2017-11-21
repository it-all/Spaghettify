<?php
declare(strict_types=1);

define('APP_ROOT', __DIR__ . '/../' );
require APP_ROOT . '/../vendor/autoload.php';

// In case of collision, env.php value overrides
$config = array_replace_recursive(
    require APP_ROOT . 'config/config.php',
    require APP_ROOT . 'config/env.php'
);

require APP_ROOT . 'init.php';

// Instantiate Slim PHP
$settings = require APP_ROOT . 'config/slim3/settings.php';
$slim = new \Slim\App($settings);

$container = $slim->getContainer();

// Set up Slim dependencies
require APP_ROOT . 'config/slim3/dependencies.php';

// Remove Slim's error handling
unset($container['errorHandler']);
unset($container['phpErrorHandler']);

// Global middleware registration
require APP_ROOT . 'config/slim3/middleware.php';

// Register routes
require APP_ROOT . 'config/slim3/routes.php';

$slim->run();
