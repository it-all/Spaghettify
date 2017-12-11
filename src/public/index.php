<?php
declare(strict_types=1);

/* sets up configuration, error handling, database connection, session */
require __DIR__ . '/../init.php';

// Instantiate Slim PHP
$settings = require __DIR__ . '/../config/slim3/settings.php';
$slim = new \Slim\App($settings);

$container = $slim->getContainer();

// Set up Slim dependencies
require __DIR__ . '/../config/slim3/dependencies.php';

// Remove Slim's error handling
unset($container['errorHandler']);
unset($container['phpErrorHandler']);

// Global middleware registration
require __DIR__ . '/../config/slim3/middleware.php';

// Register routes
require __DIR__ . '/../config/slim3/routes.php';

$slim->run();
