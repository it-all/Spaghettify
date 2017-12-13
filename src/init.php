<?php
declare(strict_types=1);
/** note: this file can also be called for cli scripts.*/

use Infrastructure\Utilities;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Infrastructure/Utilities/functions.php';


// In case of collision, env.php value overrides
$config = array_replace_recursive(
    require __DIR__ . '/config/config.php',
    require __DIR__ . '/config/env.php'
);

// used in error handler and container
$disableMailerSend = !$config['isLive'] && !$config['errors']['emailDev'];
$mailer = new \Infrastructure\Utilities\PhpMailerService(
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
    if (!isset($config['emails'][$roleEmail])) {
        throw new Exception("$roleEmail email not set in config");
    }
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

// any errors prior to the following line will not be logged
ini_set('error_log', $config['storage']['logs']['pathPhpErrors']); // even though the error handler logs errors, this ensures errors in the error handler itself or in this file after this point will be logged. note, if using slim error handling, this will log all php errors

// used in error handler and container
// do this after setting error handler in case connection fails
$dbPassword = (isset($config['database']['password'])) ? $config['database']['password'] : null;
$dbHost = (isset($config['database']['host'])) ? $config['database']['host'] : null;
$dbPort = (isset($config['database']['port'])) ? $config['database']['port'] : null;
$database = new \Infrastructure\Database\Postgres(
    $config['database']['name'],
    $config['database']['username'],
    $dbPassword,
    $dbHost,
    $dbPort
);

// used in error handler and container
$systemEventsModel = new \Infrastructure\SystemEvents\SystemEventsModel();

if ($config['errors']['logToDatabase']) {
    $errorHandler->setDatabaseAndSystemEventsModel($database, $systemEventsModel);
}

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
    if (isset($config['session']['savePath']) && strlen($config['session']['savePath']) > 0) {
        session_save_path($config['session']['savePath']);
    }
    session_start();
    $_SESSION[SESSION_LAST_ACTIVITY] = time(); // update last activity time stamp
}
