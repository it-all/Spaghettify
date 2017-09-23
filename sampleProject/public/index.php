<?php
declare(strict_types=1);

// GLOBAL CONSTANTS
define('APP_ROOT', __DIR__ . '/' );
define('VENDOR_DIR', APP_ROOT . '../vendor');

// routes
// KISS - don't use array constants because may be tricky to access in twig
define('ROUTE_HOME', 'home');
define('ROUTE_PAGE_NOT_FOUND', 'pageNotFound');
define('ROUTE_LOGIN', 'authentication.login');
define('ROUTE_LOGIN_POST', 'authentication.post.login');

// admin route prefixes
define('ROUTEPREFIX_ADMIN', 'admin');
define('ROUTEPREFIX_ADMIN_ADMINS', 'admins');
define('ROUTEPREFIX_ADMIN_ROLES', 'roles');
define('ROUTEPREFIX_ADMIN_TESTIMONIALS', 'testimonials');

// admin routes
define('ROUTE_ADMIN_HOME_DEFAULT', 'admin.home');
define('ROUTE_LOGOUT', 'authentication.logout');
// login attempts
define('ROUTE_LOGIN_ATTEMPTS', ROUTEPREFIX_ADMIN.'.logins.index');
// system events
define('ROUTE_SYSTEM_EVENTS', ROUTEPREFIX_ADMIN.'.systemEvents.index');
// admins
define('ROUTE_ADMIN_ADMINS', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.index');
define('ROUTE_ADMIN_ADMINS_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.insert');
define('ROUTE_ADMIN_ADMINS_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.post.insert');
define('ROUTE_ADMIN_ADMINS_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.update');
define('ROUTE_ADMIN_ADMINS_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.put.update');
define('ROUTE_ADMIN_ADMINS_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.delete');
// roles
define('ROUTE_ADMIN_ROLES', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.index');
define('ROUTE_ADMIN_ROLES_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.insert');
define('ROUTE_ADMIN_ROLES_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.post.insert');
define('ROUTE_ADMIN_ROLES_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.update');
define('ROUTE_ADMIN_ROLES_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.put.update');
define('ROUTE_ADMIN_ROLES_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.delete');
// testimonials
define('ROUTE_ADMIN_TESTIMONIALS', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.index');
define('ROUTE_ADMIN_TESTIMONIALS_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.insert');
define('ROUTE_ADMIN_TESTIMONIALS_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.post.insert');
define('ROUTE_ADMIN_TESTIMONIALS_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.update');
define('ROUTE_ADMIN_TESTIMONIALS_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.put.update');
define('ROUTE_ADMIN_TESTIMONIALS_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.delete');
// end routes

// END GLOBAL CONSTANTS


require VENDOR_DIR . '/autoload.php';

$env = require APP_ROOT . '/../config/env.php';

$spag = new \It_All\Spaghettify\Src\Spaghettify($env);
$spag->run();
