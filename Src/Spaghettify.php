<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src;

use It_All\Spaghettify\Src\Infrastructure\Utilities;

class Spaghettify
{
    private $envConfig;

    public function __construct(array $envConfig)
    {
        $this->checkRequiredConfig($envConfig);
        $this->envConfig = $envConfig;
    }

    private function checkRequiredConfig(array $envConfig)
    {
        if (!isset($envConfig['database'])) {
            throw new \Exception('database config not set');
        }
        if (!isset($envConfig['database']['name'])) {
            throw new \Exception('database name config not set');
        }
        if (!isset($envConfig['database']['username'])) {
            throw new \Exception('database username config not set');
        }
    }

    public function run()
    {
        require 'Infrastructure/Utilities/functions.php';

        // in case of collision, env value overrides
        $config = array_replace_recursive(
            require 'config/config.php',
            require APP_ROOT . '/../config/env.php'
        );

        // used in error handler and container
        $disableMailerSend = !$config['isLive'] && !$config['errors']['emailDev'];
        $mailerProtocol = (isset($config['phpmailer']['protocol'])) ? $config['phpmailer']['protocol'] : 'smtp';
        $smtpHost = (isset($config['phpmailer']['smtpHost'])) ? $config['phpmailer']['smtpHost'] : null;
        $smtpPort = (isset($config['phpmailer']['smtpPort'])) ? $config['phpmailer']['smtpPort'] : null;
        $mailer = new \It_All\Spaghettify\Src\Infrastructure\Utilities\PhpMailerService(
            $config['storage']['logs']['pathPhpErrors'],
            $config['emails']['service'],
            $config['businessName'],
            $mailerProtocol,
            $smtpHost,
            $smtpPort,
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

        // even though the error handler logs errors, this ensures that any errors in the error handler itself or prior to will still be logged. note, if using slim error handling, this will log all php errors
        ini_set('error_log', $config['storage']['logs']['pathPhpErrors']);

        // used in error handler and container
        // do this after setting error handler in case connection fails
        $dbPassword = (isset($config['database']['password'])) ? $config['database']['password'] : null;
        $dbHost = (isset($config['database']['host'])) ? $config['database']['host'] : null;
        $dbPort = (isset($config['database']['port'])) ? $config['database']['port'] : null;
        $database = new \It_All\Spaghettify\Src\Infrastructure\Database\Postgres(
            $config['database']['name'],
            $config['database']['username'],
            $dbPassword,
            $dbHost,
            $dbPort
        );

        // used in error handler and container
        $systemEventsModel = new \It_All\Spaghettify\Src\Infrastructure\SystemEvents\SystemEventsModel();

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
            session_save_path($config['session']['savePath']);
            session_start();
            $_SESSION[SESSION_LAST_ACTIVITY] = time(); // update last activity time stamp
        }

        // Instantiate Slim
        $settings = require 'config/slim3/settings.php';
        $slim = new \Slim\App($settings);

        $container = $slim->getContainer();

        // Set up Slim dependencies
        require 'config/slim3/dependencies.php';

        // remove Slim's Error Handling
        unset($container['errorHandler']);
        unset($container['phpErrorHandler']);

        // Middleware registration
        // handle CSRF check failures and allow Twig to access and insert CSRF fields to forms
        $slim->add(new \It_All\Spaghettify\Src\Infrastructure\Security\CsrfMiddleware($container));
        // slim CSRF check middleware
        $slim->add($container->csrf);

        // Register routes
        require 'routes.php';

        $slim->run();
    }
}
