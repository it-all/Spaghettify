<?php
declare(strict_types=1);

/** note: this file can also be called for cli scripts.*/

// GLOBAL CONSTANTS
define('APP_ROOT', __DIR__ . '/' );

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

// nav / permission options without routes
define('PERMISSION_ADMIN_SYSTEM', ROUTEPREFIX_ADMIN.'.'.'system');
define('PERMISSION_ADMIN_MARKETING', ROUTEPREFIX_ADMIN.'.'.'marketing');
define('PERMISSION_ADMIN_TESTIMONIALS', ROUTEPREFIX_ADMIN.'.'.'testimonials');

// $_SESSION var keys
define('SESSION_REQUEST_INPUT_KEY', 'requestInput');
define('SESSION_NUMBER_FAILED_LOGINS', 'numFailedLogins');
define('SESSION_LAST_ACTIVITY', 'lastActivity');
define('SESSION_USER', 'user');
define('SESSION_USER_ID', 'id');
define('SESSION_USER_NAME', 'name');
define('SESSION_USER_USERNAME', 'username');
define('SESSION_USER_ROLE', 'role');
define('SESSION_ADMIN_NOTICE', 'adminNotice');
define('SESSION_NOTICE', 'notice');
define('SESSION_GOTO_ADMIN_PATH', 'gotoAdminPath');

// END GLOBAL CONSTANTS

require APP_ROOT . '/../vendor/autoload.php';
require APP_ROOT . 'Infrastructure/Utilities/functions.php';

use It_All\Spaghettify\Src\Infrastructure\Utilities;

// in case of collision, env.php value overrides
$config = array_replace_recursive(
    require APP_ROOT . 'config/config.php',
    require APP_ROOT . 'config/env.php'
);

// used in error handler and container
$disableMailerSend = !$config['isLive'] && !$config['errors']['emailDev'];
$mailer = new \It_All\Spaghettify\Src\Infrastructure\Utilities\PhpMailerService(
    $config['storage']['logs']['pathPhpErrors'],
    $config['emails']['service'],
    $config['businessName'],
    $config['phpmailer']['protocol'],
    $config['phpmailer']['smtpHost'],
    $config['phpmailer']['smtpPort'],
    $disableMailerSend
);

// error handling
$echoErrors = !$config['isLive'];
$emailErrors = $config['isLive'] || $config['errors']['emailDev'];
$emailErrorsTo = [];
foreach ($config['errors']['emailTo'] as $roleEmail) {
    $emailErrorsTo[] = $config['emails'][$roleEmail];
}

$errorHandler = new Utilities\ErrorHandler(
    $config['storage']['logs']['pathPhpErrors'],
    $config['hostName']."/",
    $echoErrors,
    $emailErrors,
    $emailErrorsTo,
    $mailer
);

// workaround for catching some fatal errors like parse errors. note that parse errors in this file and index.php are not handled, but cause a fatal error with display (not displayed if display_errors is off in php.ini, but the ini_set call will not affect it).
register_shutdown_function(array($errorHandler, 'shutdownFunction'));
set_error_handler(array($errorHandler, 'phpErrorHandler'));
set_exception_handler(array($errorHandler, 'throwableHandler'));

error_reporting( -1 ); // all, including future types
ini_set( 'display_errors', 'off' );
ini_set( 'display_startup_errors', 'off' );

// keep this even though the error handler logs errors, so that any errors in the error handler itself or prior to will still be logged. note, if using slim error handling, this will log all php errors
ini_set('error_log', $config['storage']['logs']['pathPhpErrors']);

// used in error handler and container
// do this after setting error handler in case connection fails
$database = new \It_All\Spaghettify\Src\Infrastructure\Database\Postgres(
    $config['database']['name'],
    $config['database']['username'],
    $config['database']['password'],
    $config['database']['host'],
    $config['database']['port']
);

// used in error handler and container
$systemEventsModel = new \It_All\Spaghettify\Src\Infrastructure\SystemEvents\SystemEventsModel();

$errorHandler->setDatabaseAndSystemEventsModel($database, $systemEventsModel);

if (!Utilities\isRunningFromCommandLine()) {
    /**
     * verify/force all pages to be https. and verify/force www or not www based on Config::useWww
     * if not, REDIRECT TO PROPER SECURE PAGE
     * note this practice is ok:
     * http://security.stackexchange.com/questions/49645/actually-isnt-it-bad-to-redirect-http-to-https
     */
    if (!Utilities\isHttps() || ($config['domainUseWww'] && !Utilities\isWww()) || (!$config['domainUseWww'] && Utilities\isWww())) {
        Utilities\redirect();
    }

    /** SESSION */
    $sessionTTLseconds = $config['session']['ttlHours'] * 60 * 60;
    ini_set('session.gc_maxlifetime', (string) $sessionTTLseconds);
    ini_set('session.cookie_lifetime', (string) $sessionTTLseconds);
    if (!Utilities\sessionValidId(session_id())) {
        session_regenerate_id(true);
    }
    session_save_path($config['session']['savePath']);
    session_start();
    $_SESSION[SESSION_LAST_ACTIVITY] = time(); // update last activity time stamp
}
